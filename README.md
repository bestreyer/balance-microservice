### Технологии ###
1. symfony/flex
2. symfony/messenger
3. reactphp
4. rabbitmq
5. postgre

### Установка ###
```
cp .env.dist .env
docker-compose build
docker-compose up -d
docker-compose exec application bin/console app:generate-fake-users
```

### Workflow ###
1. Worker берет сообщения из двух очередей `request`, `try_again_messages`  и прогоняет их через MessageBus
2. MessageBus состоит из 6 Middleware:
   * ErrorHandleMiddleware - Обрабатывает ошибки
   * CreateDTOMiddleware - Создает DTO по типу сообщения
   * FillOutDTOMiddleware - Заполняет DTO
   * ValidationMiddleware - Валидирует entity
   * AccountExistsMiddleware - Валидирует наличие аккаунтов, так как используется reactphp, то в дефолтный ValidationMiddleware не получится прокинуть асинхронные constraints
   * HandleMessageMiddleware - Вызывает Handler в зависимости от класса DTO
3. Если Handler не удалось взять блокировку, то сообщение отправляется в DLX очередь (`try_again_messages.dlx`), откуда через 5 секунд появляется в `try_again_messages` и так по-кругу, пока не удасться взять блокировку
4. Если Handler удалось взять блокировку, то выполняются операции в соотвествии с требованиями и  после операции блокировка осбождается
5. Если операция прошла успешно или во время операции произошла ошибка не связанная со взятием блокировки, то генерируется событие и отправляется в `response` очередь

### TODO ###
1. Удаление истекших блокировок
2. Добавление limit на кол-во повторений взятия блокировки

### Ограничения ###
1. Не поддерживается конвертация валют

### Хранение баланса в базе: ###
Для хранения баланса был выбран тип string, чтобы поддерживать unlimited integer.
Валюта микросервиса - USD, поэтому баланс в базе хранится в центах (так как это наименьшая единица валюты, т.е '5000' -> 50.00$ )

### Cтруктура таблиц в БД ###
```
CREATE TABLE balance (
	account_id serial PRIMARY KEY,
	balance varchar(255) NOT NULL
);

CREATE TABLE balance_lock (
	account_id integer PRIMARY KEY,
	is_wait_confirmation_lock boolean NOT NULL,
	expires_at timestamp without time zone NOT NULL DEFAULT NOW()
);

CREATE INDEX ON balance_lock(expires_at);
```

balance - таблица с информацией о балансе
balance_lock - таблица блокировок

### Типы сообщений:
#### Обязательные параметры для всех сообщений ####
```
{
   "messageType": int,
   "accountId": int,
   "uuid": string - подставляется в ответе в поле "onRequest", чтобы можно было установить отношение между запросом и ответом
}
```

#### Блокировка баланса до авторизации операции ###
Пример: Блокировка аккаунта с ID = 1
```
{
   "messageType": 1,
   "accountId": 1,
   "uuid": "c360c778-a08e-4dfe-8457-9e15d50920bb"
}
```

#### Пополнение баланса ####
Пример: Пополнить аккаунт с ID = 1 на 50$ (5000 центов)
```
{
   "messageType": 2,
   "accountId": 1,
   "amount": "5000",
   "uuid": "c360c778-a08e-4dfe-8457-9e15d50920bb"
}
```

#### Разблокировка аккаунта с последущий снятием ####
Данный тип операций сработает, только если была поставлена блокировка типа waitConfirmationLock (messageType = 1)

Пример: Списать с баланса 5000 центов и снять блокировку поставленную операцией с messageType = 1
```
{
   "messageType": 3,
   "accountId": 1,
   "amount": "5000",
   "uuid": "c360c778-a08e-4dfe-8457-9e15d50920bb"
}
```

#### Перевод денег с одного аккаунта на другой ###

Пример: Перевести с аккаунта 1 на аккаунт 2 10$

```
{
   "messageType": 4,
   "accountId": 1,
   "toAccountId": 2,
   "amount": "1000",
   "uuid": "c360c778-a08e-4dfe-8457-9e15d50920bb"
}
```


#### Списать со счета ###
```
{
   "messageType": 5,
   "accountId": 1,
   "amount": "1000000",
   "uuid": "c360c778-a08e-4dfe-8457-9e15d50920bb"
}
```