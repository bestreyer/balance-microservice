<?php

namespace App\Tests\Integration;

use App\Messenger\Wrapper\Wrapper;
use function Clue\React\Block\await;
use Money\Money;
use Rhumsaa\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBus;

class IntegrationTestCase extends WebTestCase
{
    public function setUp()
    {
        self::bootKernel(['environment' => 'test']);

        $dbClient = self::$container->get('app.db.client');
        $loop = self::$container->get('app.loop');

        await($dbClient->query('TRUNCATE balance_lock'), $loop);
        await($dbClient->query('TRUNCATE balance'), $loop);
    }

    public function createBalanceRecord(int $accountId, string $balance)
    {
        $loop = self::$container->get('app.loop');
        $balanceRepository = self::$container->get('app.repository.balance');

        await($balanceRepository->create($accountId, $balance), $loop);
    }

    public function checkBalance(int $accountId, string $expectedBalance)
    {
        $loop = self::$container->get('app.loop');
        $balanceRepository = self::$container->get('app.repository.balance');

        $promise = $balanceRepository
            ->getBalance($accountId)
            ->then(function (Money $money) use ($expectedBalance) {
                $this->assertEquals(Money::USD($expectedBalance), $money);
            })
        ;

        await($promise, $loop);
    }

    public function lock(int $account, bool $isWaitConfirmationLock, $expectedLockResult)
    {
        $loop = self::$container->get('app.loop');
        $balanceLockRepository = self::$container->get('app.repository.balance_lock');

        $promise = $balanceLockRepository
            ->lock($account, $isWaitConfirmationLock)
            ->then(function ($lock) use ($expectedLockResult) {
                $this->assertEquals($expectedLockResult, $lock);
            })
        ;

        await($promise, $loop);
    }

    public function unlock(int $account, bool $isWaitConfirmationLock)
    {
        $loop = self::$container->get('app.loop');
        $balanceLockRepository = self::$container->get('app.repository.balance_lock');

        await($balanceLockRepository->unlock($account, $isWaitConfirmationLock), $loop);
    }

    public function sendMessageToMessageBus(array $message)
    {
        $c = self::$container;
        $loop = $c->get('app.loop');

        $messageBus = new MessageBus([
            $c->get('app.messenger.middleware.create_dto'),
            $c->get('app.messenger.middleware.fill_out_dto'),
            $c->get('app.messenger.middleware.validation'),
            $c->get('app.messenger.middleware.handle_message'),
        ]);

        $wrapper = new Wrapper();
        $wrapper->setData(array_merge($message, ['uuid' => Uuid::uuid4()]));

        $promise = $messageBus->dispatch($wrapper);

        await($promise, $loop);
    }
}
