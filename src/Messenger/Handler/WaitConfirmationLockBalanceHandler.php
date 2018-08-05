<?php

namespace App\Messenger\Handler;

use App\Message\DTO\WaitConfirmationLockBalanceMessageDTO;
use App\Messenger\Response\Response;

class WaitConfirmationLockBalanceHandler extends AbstractHandler
{
    public function __invoke(WaitConfirmationLockBalanceMessageDTO $lockBalanceMessageDTO)
    {
        return $this
            ->lock($lockBalanceMessageDTO->accountId, true)
            ->then(function () {
                return new Response(['httpCode' => 200]);
            });
    }
}
