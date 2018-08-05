<?php

namespace App\Tests\Integration;

use App\Exception\LockException;
use App\Message\Constant\MessageType;

class AccountToAccountMoneyTransferTest extends IntegrationTestCase
{
    public function testTransfer()
    {
        $this->createBalanceRecord(1, '1000');
        $this->createBalanceRecord(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER,
                'accountId' => 1,
                'toAccountId' => 2,
                'amount' => '1000',
            ]
        );

        $this->checkBalance(1, '0');
        $this->checkBalance(2, '3000');

        $this->lock(1, false, true);
        $this->lock(2, false, true);
    }

    public function testFailedOneLock()
    {
        $this->createBalanceRecord(1, '1000');
        $this->createBalanceRecord(2, '2000');

        $this->lock(1, false, true);

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER,
                'accountId' => 1,
                'toAccountId' => 2,
                'amount' => '1000',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockException::class, $e);
        }

        $this->checkBalance(1, '1000');
        $this->checkBalance(2, '2000');

        $this->lock(1, false, false);
        $this->lock(2, false, true);
    }

    public function testFailedBothLock()
    {
        $this->createBalanceRecord(1, '1000');
        $this->createBalanceRecord(2, '2000');

        $this->lock(1, false, true);
        $this->lock(2, false, true);

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER,
                'accountId' => 1,
                'toAccountId' => 2,
                'amount' => '1000',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockException::class, $e);
        }

        $this->checkBalance(1, '1000');
        $this->checkBalance(2, '2000');

        $this->lock(1, false, false);
        $this->lock(2, false, false);
    }
}
