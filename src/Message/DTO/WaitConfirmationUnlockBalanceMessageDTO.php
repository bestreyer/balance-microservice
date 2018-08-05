<?php

namespace App\Message\DTO;

use App\Message\DTO\Traits\MoneyTrait;

final class WaitConfirmationUnlockBalanceMessageDTO extends AbstractMessageDTO
{
    use MoneyTrait;
}
