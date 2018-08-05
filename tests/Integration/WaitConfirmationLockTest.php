<?php

namespace App\Tests\Integration;

use App\Exception\LockException;
use App\Message\Constant\MessageType;

class WaitConfirmationLockTest extends IntegrationTestCase
{
    public function testSuccess()
    {
        $this->createBalanceRecord(1, '80000');

        $this->sendMessageToMessageBus([
            'messageType' => MessageType::WAIT_CONFIRMATION_LOCK_BALANCE,
            'accountId' => 1,
        ]);

        $this->lock(1, false, false);
        $this->lock(1, true, false);

        $this->unlock(1, false);
        $this->lock(1, false, false);
    }

    public function testFailed()
    {
        $this->createBalanceRecord(1, '80000');
        $this->lock(1, false, true);

        try {
            $this->sendMessageToMessageBus([
                'messageType' => MessageType::WAIT_CONFIRMATION_LOCK_BALANCE,
                'accountId' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockException::class, $e);
        }

        $this->lock(1, false, false);
    }
}
