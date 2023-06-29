<?php

namespace Shelter\Module\Xiaomi\Core\Gateway;

class SubDevice
{
    public function __construct(
        public readonly string $model,
        public readonly string $did,
        public array           $properties = []
    )
    {
    }
}