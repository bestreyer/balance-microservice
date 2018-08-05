<?php

namespace App\Message\DTO;

use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractMessageDTO
{
    /**
     * @Assert\NotNull()
     * @Assert\Uuid()
     */
    public $uuid;

    /**
     * @Assert\NotNull()
     * @Assert\Type(type="numeric")
     */
    public $accountId;
}
