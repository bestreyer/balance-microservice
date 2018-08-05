<?php

namespace App\Tests\Integration;

use App\Message\Constant\MessageType;

class RealWorkflowTest extends IntegrationTestCase
{
    public function test()
    {
        $this->createBalanceRecord(1, '1000');
        $this->createBalanceRecord(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::REPLENISHMENT,
                'accountId' => 1,
                'amount' => '1000',
            ]
        );

        $this->checkBalance(1, '2000');
        $this->checkBalance(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::DISBURSE,
                'accountId' => 1,
                'amount' => '500',
            ]
        );

        $this->checkBalance(1, '1500');
        $this->checkBalance(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::WAIT_CONFIRMATION_LOCK_BALANCE,
                'accountId' => 1,
            ]
        );

        $this->checkBalance(1, '1500');
        $this->checkBalance(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::WAIT_CONFIRMATION_UNLOCK_BALANCE,
                'accountId' => 1,
                'amount' => '1000',
            ]
        );

        $this->checkBalance(1, '500');
        $this->checkBalance(2, '2000');

        $this->sendMessageToMessageBus(
            [
                'messageType' => MessageType::ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER,
                'accountId' => 1,
                'toAccountId' => 2,
                'amount' => '500',
            ]
        );

        $this->checkBalance(1, '0');
        $this->checkBalance(2, '2500');
    }
}
