<?php

namespace App\RabbitMQ;

use Bunny\Channel;
use Psr\Log\LoggerInterface;

class RabbitMQProducer
{
    /**
     * @var RabbitMQConnectionManager
     */
    private $rabbitConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $exchange;

    /**
     * RabbitMQProducer constructor.
     *
     * @param RabbitMQConnectionManager $rabbitConnection
     * @param LoggerInterface           $logger
     * @param string                    $exchange
     */
    public function __construct(
        RabbitMQConnectionManager $rabbitConnection,
        LoggerInterface $logger,
        string $exchange
    ) {
        $this->rabbitConnection = $rabbitConnection;
        $this->logger = $logger;
        $this->exchange = $exchange;
    }

    public function publishArray(array $data, array $headers = [])
    {
        return $this->publish(json_encode($data), $headers);
    }

    public function publish(string $data, array $headers = [])
    {
        return $this->rabbitConnection->getChannel()->then(function (Channel $channel) use ($data, $headers) {
            return $channel->publish($data, array_merge(['delivery_mode' => 2], $headers), $this->exchange, '*');
        })->then(null, function (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        });
    }
}
