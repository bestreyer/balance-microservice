<?php

namespace App\Messenger\Middleware;

use App\Messenger\DTO\Factory\DTOFactoryInterface;
use App\Messenger\Wrapper\WrapperInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;

class CreateDTOMiddleware implements MiddlewareInterface
{
    /**
     * @var DTOFactoryInterface
     */
    private $messageFactory;

    /**
     * MessageFillMiddleware constructor.
     *
     * @param DTOFactoryInterface $messageFactory
     */
    public function __construct(DTOFactoryInterface $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param WrapperInterface $message
     * @param callable         $next
     *
     * @return mixed
     */
    public function handle($message, callable $next)
    {
        if (!$message instanceof WrapperInterface) {
            return $next($message);
        }

        $data = $message->getData();

        if (!isset($data['messageType']) || !is_numeric($data['messageType'])) {
            throw new \RuntimeException('`messageType` field should not be null.');
        }

        $dto = $this->messageFactory->create($data['messageType']);

        $message->setDTO($dto);

        return $next($message);
    }
}
