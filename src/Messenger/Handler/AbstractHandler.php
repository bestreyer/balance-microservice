<?php

namespace App\Messenger\Handler;

use App\Exception\LockException;
use App\Exception\NotEnoghtMoneyException;
use App\Exception\LockNotExistsException;
use App\Repository\BalanceLockRepository;
use App\Repository\BalanceRepository;
use Money\Money;
use React\Promise\Promise;

abstract class AbstractHandler
{
    /**
     * @var BalanceLockRepository
     */
    protected $balanceLockRepository;

    /**
     * @var BalanceRepository
     */
    protected $balanceRepository;

    /**
     * AbstractHandler constructor.
     *
     * @param BalanceLockRepository $balanceLockRepository
     * @param BalanceRepository     $balanceRepository
     */
    public function __construct(BalanceLockRepository $balanceLockRepository, BalanceRepository $balanceRepository)
    {
        $this->balanceLockRepository = $balanceLockRepository;
        $this->balanceRepository = $balanceRepository;
    }

    /**
     * @param int  $accountId
     * @param bool $isWaitConfirmationLock
     *
     * @return \React\Promise\PromiseInterface
     */
    protected function lock(int $accountId, bool $isWaitConfirmationLock = false, int $lockExpires = 2147483648)
    {
        return $this->balanceLockRepository->lock(
            $accountId,
            $isWaitConfirmationLock,
            $lockExpires
        )->then(function ($lock) {
            if (false === $lock) {
                throw new LockException();
            }
        });
    }

    protected function checkBalance(int $accountId, Money $usdMoney)
    {
        return $this
            ->balanceRepository
            ->getBalance($accountId)
            ->then(function (Money $balance) use ($usdMoney) {
                if ($balance->lessThan($usdMoney)) {
                    throw new NotEnoghtMoneyException("Account don't have enough money");
                }

                return $balance;
            })
        ;
    }

    protected function unlockAfterOperation(Promise $promise, int $accountId, $isWaitConfirmationLock)
    {
        return $promise
            ->then(function () use ($accountId, $isWaitConfirmationLock) {
                return $this->balanceLockRepository->unlock($accountId, $isWaitConfirmationLock);
            }, function (\Throwable $e) use ($accountId, $isWaitConfirmationLock) {
                if (!$e instanceof LockException && !$e instanceof LockNotExistsException) {
                    return $this
                        ->balanceLockRepository
                        ->unlock($accountId, $isWaitConfirmationLock)
                        ->then(function () use ($e) {
                            throw $e;
                        });
                }

                throw $e;
            })
        ;
    }
}
