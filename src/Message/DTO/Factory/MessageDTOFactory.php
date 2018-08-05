<?php

namespace App\Message\DTO\Factory;

use App\Message\Constant\MessageType;
use App\Message\DTO\WaitConfirmationLockBalanceMessageDTO;
use App\Message\DTO\ReplenishmentMessageDTO;
use App\Message\DTO\WaitConfirmationUnlockBalanceMessageDTO;
use App\Message\DTO\AccountToAccountMoneyTransferMessageDTO;
use App\Message\DTO\DisburseMessageDTO;
use App\Message\Exception\NotFoundMessageDTOException;
use App\Messenger\DTO\Factory\DTOFactoryInterface;

class MessageDTOFactory implements DTOFactoryInterface
{
    protected $mapping = [
        MessageType::WAIT_CONFIRMATION_LOCK_BALANCE => WaitConfirmationLockBalanceMessageDTO::class,
        MessageType::REPLENISHMENT => ReplenishmentMessageDTO::class,
        MessageType::WAIT_CONFIRMATION_UNLOCK_BALANCE => WaitConfirmationUnlockBalanceMessageDTO::class,
        MessageType::ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER => AccountToAccountMoneyTransferMessageDTO::class,
        MessageType::DISBURSE => DisburseMessageDTO::class,
    ];

    public function create(int $messageType)
    {
        if (!array_key_exists($messageType, $this->mapping)) {
            throw new NotFoundMessageDTOException(
                sprintf("A dto hasn't found for %d message type.", $messageType)
            );
        }

        return new $this->mapping[$messageType]();
    }
}
