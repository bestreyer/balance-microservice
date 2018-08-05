<?php

namespace App\Tests\Integration;

use App\Exception\LockException;
use App\Message\Constant\MessageType;

class ReplenishmentTest extends IntegrationTestCase
{
    public function testSuccess()
    {
        $this->createBalanceRecord(1, '8000');
        $this->sendMessageToMessageBus([
            'messageType' => MessageType::REPLENISHMENT,
            'accountId' => 1,
            'amount' => '1000',
        ]);

        $this->checkBalance(1, '9000');

        $this->lock(1, false, true);
    }

    public function testFailed()
    {
        $this->createBalanceRecord(1, '8000');
        $this->lock(1, false, true);

        $this->expectException(LockException::class);

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::REPLENISHMENT,
            'accountId' => 1,
            'amount' => '1000',
        ]);

        $this->lock(1, false, true);
    }
}
