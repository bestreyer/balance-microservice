<?php

namespace App\Message\DTO;

use App\Message\DTO\Traits\MoneyTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class AccountToAccountMoneyTransferMessageDTO extends AbstractMessageDTO
{
    use MoneyTrait;

    /**
     * @Assert\NotNull
     * @Assert\Type(type="numeric")
     */
    public $toAccountId;
}
