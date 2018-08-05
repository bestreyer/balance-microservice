<?php

namespace App\Tests\Unit\Messenger\Middleware;

use App\Messenger\Middleware\FillOutDTOMiddleware;
use App\Messenger\Wrapper\Wrapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FillOutDTOMiddlewareTest extends TestCase
{
    public function testHandle()
    {
        $mockDTO = $this->createMock(\StdClass::class);
        $mockDTO->username = null;
        $mockDTO->password = null;

        $wrapper = new Wrapper();
        $wrapper->setData(['username' => 'dmitrey', 'password' => '12345']);
        $wrapper->setDTO($mockDTO);

        $middleware = new FillOutDTOMiddleware(new ObjectNormalizer());
        $returnResult = $middleware->handle($wrapper, function ($dto) {
            return $dto;
        });

        $this->assertEquals($mockDTO, $returnResult);
        $this->assertEquals('dmitrey', $returnResult->username);
        $this->assertEquals('12345', $returnResult->password);
    }
}
