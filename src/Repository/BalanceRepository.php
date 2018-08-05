<?php

namespace App\Repository;

use Money\Money;
use function React\Promise\all;
use function React\Promise\resolve;

class BalanceRepository extends AbstractRepository
{
    /**
     * @param int    $accountId
     * @param string $balance
     *
     * @return \React\Promise\PromiseInterface
     */
    public function create(int $accountId, string $balance)
    {
        return $this->dbClient->executeStatement(
            'INSERT INTO balance(account_id, balance) VALUES($1, $2)',
            [$accountId, $balance]
        );
    }

    /**
     * @param string $balance
     *
     * @return \React\Promise\PromiseInterface
     */
    public function createWithNextAccountId(string $balance)
    {
        return $this->dbClient->executeStatement(
            'INSERT INTO balance(balance) VALUES($1)',
            [$balance]
        );
    }

    /**
     * @param int $accountId
     *
     * @return \React\Promise\PromiseInterface
     */
    public function exists(int $accountId)
    {
        return $this->dbClient->executeStatement(
            'SELECT account_id FROM balance WHERE account_id = $1',
            [$accountId]
        )->then(function ($row) {
            return !empty($row);
        });
    }

    public function getBalance(int $accountId)
    {
        $sql = 'SELECT balance FROM balance WHERE account_id = $1';

        return $this
            ->dbClient
            ->executeStatement($sql, [$accountId])
            ->then(function ($row) {
                return Money::USD($row['balance']);
            })
        ;
    }

    public function setBalance(int $accountId, string $balance)
    {
        $sql = 'UPDATE balance SET balance = $2 WHERE account_id = $1';

        return $this->dbClient->executeStatement($sql, [$accountId, $balance]);
    }

    /**
     * @param int    $firstAccountId
     * @param string $balanceFirst
     * @param int    $secondAccountId
     * @param string $secondsBalance
     *
     * @return \React\Promise\PromiseInterface
     */
    public function setBalanceTwoUsers(
        int $firstAccountId,
        string $balanceFirst,
        int $secondAccountId,
        string $secondsBalance
    ) {
        $sql = sprintf('
            BEGIN;
                UPDATE balance SET balance = \'%s\' WHERE account_id = %d;
                UPDATE balance SET balance = \'%s\' WHERE account_id = %d;
            COMMIT;
        ', $balanceFirst, $firstAccountId, $secondsBalance, $secondAccountId);

        return $this->dbClient->query($sql);
    }

    /**
     * @param int   $accountId
     * @param Money $usdMoney
     *
     * @return null|\React\Promise\FulfilledPromise|\React\Promise\Promise|\React\Promise\PromiseInterface
     */
    public function disburse(int $accountId, Money $usdMoney)
    {
        if ($usdMoney->isZero()) {
            return resolve();
        }

        return $this
            ->getBalance($accountId)
            ->then(function (Money $money) use ($usdMoney, $accountId) {
                $balance = $money->subtract($usdMoney);

                return all([
                    $this->setBalance($accountId, $balance->getAmount()),
                    $balance,
                ]);
            })->then(function ($result) {
                return $result[1];
            })
        ;
    }

    /**
     * @param int   $accountId
     * @param Money $usdMoney
     *
     * @return null|\React\Promise\FulfilledPromise|\React\Promise\Promise|\React\Promise\PromiseInterface
     */
    public function replenishment(int $accountId, Money $usdMoney)
    {
        if ($usdMoney->isZero()) {
            return resolve();
        }

        return $this
            ->getBalance($accountId)
            ->then(function (Money $money) use ($usdMoney, $accountId) {
                $balance = $money->add($usdMoney);

                return all([
                    $this->setBalance($accountId, $balance->getAmount()),
                    $balance,
                ]);
            })->then(function ($result) {
                return $result[1];
            })
        ;
    }
}
