<?php

namespace Shelter\Module\Xiaomi;

class Device
{
    public function __construct(
        public readonly string $did,
        public readonly string $host,
        public readonly string $token,
        public array           $runtime = [],
        public int             $handshakeAt = 0
    )
    {
    }
}