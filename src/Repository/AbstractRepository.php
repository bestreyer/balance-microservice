<?php

namespace App\Repository;

use App\Postgre\Client;

abstract class AbstractRepository
{
    /**
     * @var Client
     */
    protected $dbClient;

    /**
     * AbstractRepository constructor.
     *
     * @param Client $dbClient
     */
    public function __construct(Client $dbClient)
    {
        $this->dbClient = $dbClient;
    }
}
