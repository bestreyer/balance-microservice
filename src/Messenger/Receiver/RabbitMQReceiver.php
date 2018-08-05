<?php

namespace App\Messenger\Receiver;

use App\Messenger\Response\ResponseInterface;
use App\Messenger\Wrapper\Wrapper;
use App\RabbitMQ\RabbitMQConnectionManager;
use App\RabbitMQ\RabbitMQProducer;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Messenger\MessageBus;

class RabbitMQReceiver
{
    /**
     * @var RabbitMQConnectionManager
     */
    private $rabbitMQManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var RabbitMQProducer
     */
    private $responseProducer;

    /**
     * RabbitMQReceiver constructor.
     *
     * @param RabbitMQConnectionManager $rabbitMQManager
     * @param LoggerInterface           $logger
     * @param string                    $queueName
     * @param MessageBus                $messageBus
     */
    public function __construct(
        RabbitMQConnectionManager $rabbitMQManager,
        RabbitMQProducer $responseProducer,
        LoggerInterface $logger,
        string $queueName,
        MessageBus $messageBus
    ) {
        $this->rabbitMQManager = $rabbitMQManager;
        $this->logger = $logger;
        $this->queueName = $queueName;
        $this->messageBus = $messageBus;
        $this->responseProducer = $responseProducer;
    }

    public function receive(): void
    {
        $this
            ->rabbitMQManager
            ->getChannel()
            ->then(function (Channel $channel) {
                $channel->consume([$this, 'consume'], $this->queueName);
            });
    }

    public function consume(Message $message, Channel $channel, Client $client)
    {
        $jsonData = json_decode($message->content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $channel->ack($message);

            return $this->logger->error(sprintf('Invalid json %s', $message->content));
        }

        $wrapper = new Wrapper();
        $wrapper->setData($jsonData);

        $responsePromise = $this->messageBus->dispatch($wrapper);

        $this->handleResponsePromise($responsePromise, $jsonData, $channel, $message);
    }

    /**
     * @param $responsePromise
     * @param $jsonData
     * @param Channel $channel
     * @param Message $message
     *
     * @return bool|PromiseInterface
     */
    private function handleResponsePromise($responsePromise, $jsonData, Channel $channel, Message $message)
    {
        if (!$responsePromise instanceof PromiseInterface) {
            return $channel->ack($message);
        }

        $responsePromise->then(function ($response) use ($jsonData) {
            if (!$response instanceof ResponseInterface) {
                return;
            }

            return $this->responseProducer->publishArray(array_merge([
                'onRequest' => array_key_exists('uuid', $jsonData) ? $jsonData['uuid'] : 'none',
                'onMessageType' => array_key_exists('messageType', $jsonData) ? $jsonData['messageType'] : 'none',
            ], $response->getData()));
        }, function (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        })->done(function () use ($channel, $message) {
            return $channel->ack($message);
        });
    }
}
