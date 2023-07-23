<?php

namespace inisire\Xiaomi\Core\Device;

use inisire\fibers\Network\SocketFactory;
use inisire\Logging\NullLogger;
use inisire\Protocol\MiIO\Connection;
use inisire\Protocol\MiIO\Device;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class GenericDevice implements LoggerAwareInterface
{
    private Device $miio;

    protected LoggerInterface $logger;

    public function __construct(
        private readonly string $host,
        private readonly string $token,
        ?LoggerInterface        $logger = null,
    )
    {
        $this->logger = $logger ?? new NullLogger();
        $this->miio = new Device(new Connection($this->host, $this->token, $this->logger, new SocketFactory()));
    }

    public function call(string $method, array $parameters = []): array
    {
        $response = $this->miio->call($method, $parameters);

        return $response->getData();
    }

    public function info(): array
    {
        return $this->call('miIO.Info');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}