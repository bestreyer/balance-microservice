<?php

namespace App\Messenger\Wrapper;

interface WrapperInterface
{
    public function setData(array $data);

    public function getData(): array;

    public function setDTO($dto);

    public function getDTO();
}
