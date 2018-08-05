<?php

namespace App\Messenger\Handler;

use App\Message\DTO\DisburseMessageDTO;
use App\Messenger\Response\Response;
use Money\Money;

class DisburseHandler extends AbstractHandler
{
    public function __invoke(DisburseMessageDTO $dto)
    {
        $money = Money::USD($dto->amount);

        $promise = $this
            ->lock($dto->accountId, false, time() + 60)
            ->then(function () use ($dto, $money) {
                return $this->checkBalance($dto->accountId, $money);
            })->then(function () use ($dto, $money) {
                return $this->balanceRepository->disburse($dto->accountId, $money);
            })
        ;

        return $this
            ->unlockAfterOperation($promise, $dto->accountId, false)
            ->then(function () {
                return new Response(['httpCode' => 200]);
            });
    }
}
