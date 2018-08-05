<?php

namespace App\Tests\Integration;

use App\Exception\LockNotExistsException;
use App\Exception\NotEnoghtMoneyException;
use App\Message\Constant\MessageType;

class WaitConfirmationUnLockTest extends IntegrationTestCase
{
    public function testFailed()
    {
        $this->createBalanceRecord(1, '80000');

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::WAIT_CONFIRMATION_UNLOCK_BALANCE,
                'accountId' => 1,
                'amount' => '0',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockNotExistsException::class, $e);
        }

        $this->lock(1, true, true);
    }

    public function testDontEnoughMoney()
    {
        $this->createBalanceRecord(1, '1000');

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::WAIT_CONFIRMATION_LOCK_BALANCE,
            'accountId' => 1,
        ]);

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::WAIT_CONFIRMATION_UNLOCK_BALANCE,
                'accountId' => 1,
                'amount' => '900000',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(NotEnoghtMoneyException::class, $e);
        }

        $this->lock(1, true, true);
    }

    public function testSuccess()
    {
        $this->createBalanceRecord(1, '80000');

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::WAIT_CONFIRMATION_LOCK_BALANCE,
            'accountId' => 1,
        ]);

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::WAIT_CONFIRMATION_UNLOCK_BALANCE,
            'accountId' => 1,
            'amount' => '1000',
        ]);

        $this->checkBalance(1, '79000');

        $this->lock(1, true, true);
    }
}
