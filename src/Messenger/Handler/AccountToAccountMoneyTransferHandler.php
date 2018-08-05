<?php

namespace App\Messenger\Handler;

use App\Exception\LockException;
use App\Message\DTO\AccountToAccountMoneyTransferMessageDTO;
use App\Messenger\Response\Response;
use Money\Money;
use function React\Promise\all;

class AccountToAccountMoneyTransferHandler extends AbstractHandler
{
    public function __invoke(AccountToAccountMoneyTransferMessageDTO $dto)
    {
        $money = Money::USD($dto->amount);

        return $this
            ->acquireLock($dto)
            ->then(function () use ($dto, $money) {
                return all([
                    $this->checkBalance($dto->accountId, $money),
                    $this->balanceRepository->getBalance($dto->toAccountId),
                ]);
            })->then(function ($balances) use ($dto, $money) {
                return $this->balanceRepository->setBalanceTwoUsers(
                    $dto->accountId,
                    $balances[0]->subtract($money)->getAmount(),
                    $dto->toAccountId,
                    $balances[1]->add($money)->getAmount()
                );
            })->then(function () use ($dto) {
                return $this->unlock($dto);
            })->then(function () {
                return new Response(['httpCode' => 200]);
            })->otherwise(function (\Throwable $e) use ($dto) {
                return $this->unlock($dto, $e);
            });
    }

    private function unlock(AccountToAccountMoneyTransferMessageDTO  $dto, \Throwable $e = null)
    {
        if ($e instanceof LockException) {
            throw $e;
        }

        return all([
            $this->balanceLockRepository->unlock($dto->accountId, false),
            $this->balanceLockRepository->unlock($dto->toAccountId, false),
        ])->then(function () use ($e) {
            if (null !== $e) {
                throw $e;
            }
        });
    }

    private function acquireLock($dto)
    {
        return all([
            $this->balanceLockRepository->lock($dto->accountId, false, time() + 30),
            $this->balanceLockRepository->lock($dto->toAccountId, false, time() + 30),
        ])->then(function ($locks) use ($dto) {
            if (true === $locks[0] && true === $locks[1]) {
                return;
            }

            if (false === $locks[0] && true === $locks[1]) {
                $this->balanceLockRepository->unlock($dto->toAccountId);
            }

            if (true === $locks[0] && false === $locks[1]) {
                $this->balanceLockRepository->unlock($dto->accountId);
            }

            throw new LockException();
        });
    }
}
