<?php

namespace App\Messenger\Handler;

use App\Message\DTO\ReplenishmentMessageDTO;
use App\Messenger\Response\Response;
use Money\Money;

class ReplenishmentHandler extends AbstractHandler
{
    public function __invoke(ReplenishmentMessageDTO $dto)
    {
        $promise = $this
            ->lock($dto->accountId, false, time() + 30)
            ->then(function () use ($dto) {
                return $this->balanceRepository->replenishment($dto->accountId, Money::USD($dto->amount));
            })
        ;

        return $this
            ->unlockAfterOperation($promise, $dto->accountId, false)
            ->then(function () {
                return new Response(['httpCode' => 200]);
            })
        ;
    }
}
