<?php

namespace App\Message\DTO;

use App\Message\DTO\Traits\MoneyTrait;

final class ReplenishmentMessageDTO extends AbstractMessageDTO
{
    use MoneyTrait;
}
