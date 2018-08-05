<?php

namespace App\Message\DTO;

use App\Message\DTO\Traits\MoneyTrait;

final class DisburseMessageDTO extends AbstractMessageDTO
{
    use MoneyTrait;
}
