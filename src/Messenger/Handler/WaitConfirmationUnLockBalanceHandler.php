<?php

namespace App\Messenger\Handler;

use App\Exception\LockNotExistsException;
use App\Message\DTO\WaitConfirmationUnlockBalanceMessageDTO;
use App\Messenger\Response\Response;
use Money\Money;

class WaitConfirmationUnLockBalanceHandler extends AbstractHandler
{
    public function __invoke(WaitConfirmationUnlockBalanceMessageDTO $dto)
    {
        $money = Money::USD($dto->amount);

        $promise = $this
            ->checkLock($dto)
            ->then(function () use ($money, $dto) {
                return $this->checkBalance($dto->accountId, $money);
            })->then(function () use ($money, $dto) {
                return $this->balanceRepository->disburse($dto->accountId, $money);
            })
        ;

        return $this
            ->unlockAfterOperation($promise, $dto->accountId, true)
            ->then(function () {
                return new Response(['httpCode' => 200]);
            });
    }

    /**
     * @param WaitConfirmationUnlockBalanceMessageDTO $dto
     *
     * @return \React\Promise\PromiseInterface
     */
    private function checkLock(WaitConfirmationUnlockBalanceMessageDTO $dto)
    {
        return $this
            ->balanceLockRepository
            ->waitConfirmationLockExists($dto->accountId)
            ->then(function ($exists) use ($dto) {
                if (!$exists) {
                    throw new LockNotExistsException('You should lock balance before unlock it');
                }
            });
    }
}
