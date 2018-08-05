<?php

namespace App\Messenger\Response;

class Response implements ResponseInterface
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
