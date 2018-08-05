<?php

namespace App\Tests\Integration;

use App\Exception\LockException;
use App\Exception\NotEnoghtMoneyException;
use App\Message\Constant\MessageType;

class DisburseTest extends IntegrationTestCase
{
    public function testSuccess()
    {
        $this->createBalanceRecord(1, '8000');

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::DISBURSE,
            'accountId' => 1,
            'amount' => '1000',
        ]);

        $this->checkBalance(1, '7000');

        $this->lock(1, false, true);
    }

    public function testFailed()
    {
        $this->createBalanceRecord(1, '8000');
        $this->lock(1, false, true);

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::DISBURSE,
                'accountId' => 1,
                'amount' => '1000',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockException::class, $e);
        }

        $this->lock(1, false, false);
    }

    public function testDontEnoughtMoney()
    {
        $this->createBalanceRecord(1, '8000');

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::DISBURSE,
                'accountId' => 1,
                'amount' => '1000000',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(NotEnoghtMoneyException::class, $e);
        }

        $this->lock(1, false, true);
    }
}
