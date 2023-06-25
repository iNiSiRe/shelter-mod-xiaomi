<?php

namespace Shelter\Module\Xiaomi\Event;

use inisire\NetBus\Event;

class GatewaySubDeviceUpdate implements Event
{
    public function __construct(
        private readonly string $did,
        private readonly array $properties = []
    )
    {
    }

    public function getName(): string
    {
        return 'XiaomiGateway.SubDeviceUpdate';
    }

    public function getData(): array
    {
        return [
            'did' => $this->did,
            'properties' => $this->properties
        ];
    }
}