<?php

namespace App\Messenger\Wrapper;

class Wrapper implements WrapperInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var object
     */
    private $dto;

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setDTO($dto)
    {
        $this->dto = $dto;
    }

    public function getDTO()
    {
        return $this->dto;
    }
}
