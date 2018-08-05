<?php

namespace App\Repository;

class BalanceLockRepository extends AbstractRepository
{
    /**
     * @param int $accountId
     * @param int $messageType
     * @param int $lockExpires
     *
     * @return \React\Promise\PromiseInterface
     */
    public function lock(int $accountId, bool $isWaitConfirmationLock = false, int $lockExpires = 2147483648)
    {
        $sql = '
            INSERT INTO balance_lock(account_id, is_wait_confirmation_lock, expires_at)
            VALUES($1, $2, to_timestamp($3)) 
        ';

        return $this->dbClient->executeStatement(
            $sql,
            [$accountId, $isWaitConfirmationLock, $lockExpires]
        )->then(function () {
            return true;
        }, function (\Exception $e) {
            if (false !== mb_strpos($e->getMessage(), 'duplicate key value')) {
                return false;
            }

            throw $e;
        });
    }

    /**
     * @param int  $accountId
     * @param bool $isWaitConfirmationLock
     */
    public function unlock(int $accountId, bool $isWaitConfirmationLock = false)
    {
        $sql = 'DELETE FROM balance_lock WHERE account_id = $1 and is_wait_confirmation_lock = $2';

        return $this->dbClient->executeStatement($sql, [$accountId, $isWaitConfirmationLock]);
    }

    public function waitConfirmationLockExists(int $accountId)
    {
        $sql = 'SELECT * FROM balance_lock WHERE account_id = $1 and is_wait_confirmation_lock = true';

        return $this
            ->dbClient
            ->executeStatement($sql, [$accountId])
            ->then(function ($row) {
                return !empty($row);
            });
    }

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function deleteExpiresLocks()
    {
        $sql = 'DELETE FROM balance_lock WHERE expires_at > to_timestamp($1)';

        return $this->dbClient->executeStatement($sql, [time()]);
    }
}
