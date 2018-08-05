<?php

namespace App\RabbitMQ;

use Bunny\Async\Client;
use Bunny\Channel;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

class RabbitMQConnectionManager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var PromiseInterface
     */
    private $promiseClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AsyncClient constructor.
     *
     * @param LoopInterface   $loop
     * @param array           $options
     * @param LoggerInterface $logger
     */
    public function __construct(LoopInterface $loop, array $options, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = new Client($loop, $options);

        $this->connect();
    }

    /**
     * @return PromiseInterface
     */
    public function connect()
    {
        $this->promiseClient = $this->client->connect(function (Client $client) {
            $this->logger->error('RabbitMQ connection successful');
            $this->client = $client;

            return $client;
        }, function (\Throwable $e) {
            $this->logger->error('RabbitMQ connection failed');
        });

        return $this->promiseClient;
    }

    /**
     * @return Channel|PromiseInterface
     */
    public function getChannel()
    {
        return $this->promiseClient->then(function (Client $client) {
            if (!$client->isConnected()) {
                return $this->connect();
            }

            return $client;
        })->then(function (Client $client) {
            return $client->channel();
        });
    }
}
