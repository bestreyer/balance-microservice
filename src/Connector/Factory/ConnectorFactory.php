<?php

namespace App\Connector\Factory;

use React\Dns\Config\Config;
use React\Dns\Resolver\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;

class ConnectorFactory
{
    /**
     * @var \React\Dns\Resolver\Resolver
     */
    private $dnsResolver;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * ConnectorFactory constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop, ?string $dnsServerIp = null)
    {
        if (null === $dnsServerIp || 'null' === $dnsServerIp) {
            $dnsConfig = Config::loadSystemConfigBlocking();
            $dnsServerIp = $dnsConfig->nameservers ? reset($dnsConfig->nameservers) : '8.8.8.8';
        }

        $this->loop = $loop;
        $this->dnsResolver = (new Factory())->create($dnsServerIp, $loop);
    }

    /**
     * @return Connector
     */
    public function create()
    {
        return new Connector($this->loop, [
            'dns' => $this->dnsResolver,
        ]);
    }
}
