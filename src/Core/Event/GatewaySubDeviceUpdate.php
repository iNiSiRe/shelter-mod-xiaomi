<?php

namespace Shelter\Module\Xiaomi\Core\Event;

class GatewaySubDeviceUpdate
{
    public function __construct(
        private readonly string $did,
        private readonly array $properties = []
    )
    {
    }

    public function getDid(): string
    {
        return $this->did;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}