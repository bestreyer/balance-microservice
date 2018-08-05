<?php

namespace App\Message\Constant;

class MessageType
{
    public const WAIT_CONFIRMATION_LOCK_BALANCE = 1;
    public const REPLENISHMENT = 2;
    public const WAIT_CONFIRMATION_UNLOCK_BALANCE = 3;
    public const ACCOUNT_TO_ACCOUNT_MONEY_TRANSFER = 4;
    public const DISBURSE = 5;
}
