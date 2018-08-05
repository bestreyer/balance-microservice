<?php

namespace App\Postgre;

class Client
{
    /**
     * @var \PgAsync\Client
     */
    private $client;

    /**
     * Client constructor.
     *
     * @param \PgAsync\Client $client
     */
    public function __construct(\PgAsync\Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $query
     *
     * @return \React\Promise\PromiseInterface
     */
    public function query(string $query)
    {
        return $this->client->query($query)->toPromise();
    }

    /**
     * @param string $query
     * @param array  $parameters
     *
     * @return \React\Promise\PromiseInterface
     */
    public function executeStatement(string $query, array $parameters)
    {
        return $this->client->executeStatement($query, $parameters)->toPromise();
    }
}
