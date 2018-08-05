<?php

namespace App\Tests\Unit\Messenger\Middleware;

use App\Messenger\DTO\Factory\DTOFactoryInterface;
use App\Messenger\Middleware\CreateDTOMiddleware;
use App\Messenger\Wrapper\Wrapper;
use PHPUnit\Framework\TestCase;

class CreateDTOMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        $mockFirstDTO = $this->createMock(\StdClass::class);
        $mockSecondDTO = $this->createMock(\StdClass::class);
        $mockSecondDTO->testDTOProperty = 20;

        $mockDTOFactory = $this->createMock(DTOFactoryInterface::class);

        $mockDTOFactory
            ->method('create')
            ->willReturnCallback(function ($arg) use ($mockFirstDTO, $mockSecondDTO) {
                return 1 === $arg ? $mockFirstDTO : $mockSecondDTO;
            })
        ;

        $this->handle(1, $mockDTOFactory, $mockFirstDTO);
        $this->handle(2, $mockDTOFactory, $mockSecondDTO);
    }

    private function handle(int $messageType, DTOFactoryInterface $mockDTOFactory, $expectedDTO)
    {
        $wrapper = new Wrapper();
        $wrapper->setData(['messageType' => $messageType]);

        $middleware = new CreateDTOMiddleware($mockDTOFactory);

        $returnValue = $middleware->handle($wrapper, function ($message) {
            return $message;
        });

        $this->assertEquals($wrapper, $returnValue);
        $this->assertEquals($expectedDTO, $returnValue->getDTO());
    }
}
