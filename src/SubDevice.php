<?php

namespace Shelter\Module\Xiaomi;

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