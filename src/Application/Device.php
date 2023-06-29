<?php

namespace Shelter\Module\Xiaomi\Application;

class Device
{
    public function __construct(
        public readonly string $id,
        public array $properties
    )
    {
    }
}