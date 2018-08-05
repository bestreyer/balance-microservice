<?php

namespace App\Message\DTO\Traits;

use Symfony\Component\Validator\Constraints as Assert;

trait MoneyTrait
{
    /**
     * @Assert\NotNull
     * @Assert\Type(type="numeric")
     */
    public $amount;
}
