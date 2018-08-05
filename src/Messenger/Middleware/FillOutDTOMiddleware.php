<?php

namespace App\Messenger\Middleware;

use App\Messenger\Wrapper\WrapperInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FillOutDTOMiddleware implements MiddlewareInterface
{
    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    /**
     * FillOutDTOMiddleware constructor.
     *
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
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

        $dto = $message->getDTO();
        $data = $message->getData();

        $this->denormalizer->denormalize($data, get_class($dto), null, ['object_to_populate' => $dto]);

        return $next($dto);
    }
}
