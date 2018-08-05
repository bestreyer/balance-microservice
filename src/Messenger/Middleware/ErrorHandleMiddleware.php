<?php

namespace App\Messenger\Middleware;

use App\Exception\LockException;
use App\Exception\LockNotExistsException;
use App\Exception\LogicException;
use App\Messenger\Response\Response;
use App\Messenger\Wrapper\WrapperInterface;
use App\RabbitMQ\RabbitMQProducer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use function React\Promise\resolve;

class ErrorHandleMiddleware implements MiddlewareInterface
{
    /**
     * @var RabbitMQProducer
     */
    private $responseProducer;

    /**
     * @var RabbitMQProducer
     */
    private $tryAgainProducer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ErrorHandlerMiddleware constructor.
     *
     * @param RabbitMQProducer $responseProducer
     * @param RabbitMQProducer $tryAgainProducer
     * @param LoggerInterface  $logger
     */
    public function __construct(
        RabbitMQProducer $responseProducer,
        RabbitMQProducer $tryAgainProducer,
        LoggerInterface $logger
    ) {
        $this->responseProducer = $responseProducer;
        $this->tryAgainProducer = $tryAgainProducer;
        $this->logger = $logger;
    }

    /**
     * @param object $message
     *
     * @return mixed
     */
    public function handle($message, callable $next)
    {
        if (!$message instanceof WrapperInterface) {
            return $next($message);
        }

        try {
            return $next($message)
                ->otherwise(function (ValidationFailedException $validationFailedException) {
                    return $this->handleValidationErrors($validationFailedException);
                })->otherwise(function (LockException $exception) use ($message) {
                    return $this->handleLockException($exception, $message);
                })->otherwise(function (LockNotExistsException $e) {
                    return $this->createErrorResponse(['global' => $e->getMessage()]);
                })->otherwise(function (LogicException $e) {
                    return $this->createErrorResponse(['global' => $e->getMessage()]);
                })->otherwise(function (\Throwable $e) {
                    return $this->handleInternalErrorException($e);
                });
        } catch (ValidationFailedException $validationFailedException) {
            return resolve($this->handleValidationErrors($validationFailedException));
        } catch (\Throwable $e) {
            return resolve($this->handleInternalErrorException($e));
        }
    }

    private function handleInternalErrorException(\Throwable $e)
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        return $this->createErrorResponse(['global' => 'Internal Server Error. See log file'], 500);
    }

    private function handleValidationErrors(ValidationFailedException $validationFailedException)
    {
        $errors = [];

        $violations = $validationFailedException->getViolations();

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->createErrorResponse($errors);
    }

    private function createErrorResponse(array $errors, int $httpCode = 400)
    {
        return new Response([
            'errors' => $errors,
            'httpCode' => $httpCode,
        ]);
    }

    private function handleLockException(LockException $exception, WrapperInterface $message)
    {
        return $this->tryAgainProducer->publishArray($message->getData(), ['expiration' => 5000]);
    }
}
