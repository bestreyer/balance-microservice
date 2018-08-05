<?php

namespace App\Messenger\DTO\Factory;

interface DTOFactoryInterface
{
    /**
     * @param int $type
     *
     * @return mixed
     */
    public function create(int $type);
}
