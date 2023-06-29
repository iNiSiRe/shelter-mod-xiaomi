<?php

namespace Shelter\Module\Xiaomi\Application\Event;

use inisire\NetBus\Event\EventInterface;

class DeviceUpdateEvent implements EventInterface
{
    public function __construct(
        private readonly string $deviceId,
        private readonly array $properties = []
    )
    {
    }

    public function getName(): string
    {
        return 'Shelter.DeviceUpdate';
    }

    public function getData(): array
    {
        return [
            'deviceId' => $this->deviceId,
            'properties' => $this->properties
        ];
    }
}