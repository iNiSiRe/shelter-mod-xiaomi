<?php

namespace Shelter\Module\Xiaomi\Service;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class MiioHandshakeNetworkListener
{
    public function __construct(
        private LoopInterface $loop,
        private LoggerInterface $logger
    )
    {
    }

    public function start()
    {
        $factory = new \React\Datagram\Factory($this->loop);

        $factory
            ->createServer('0.0.0.0:54321')
            ->then(function (\React\Datagram\Socket $server) {
                $server->on('message', function($message, $address, $server) {
                    if ($message == Miio::createHelloPacket() || $message == Miio::createHelloPacket('00000000000000000000000000000000')) {
                        $this->logger->debug("Received handshake from $address", ['channel' => __CLASS__]);
                        $server->send(Miio::buildAck(), $address);
                    } else {
                        $message = bin2hex($message);
                        $this->logger->debug("Received '$message' from $address", ['channel' => __CLASS__]);
                    }
                });
            });
    }
}