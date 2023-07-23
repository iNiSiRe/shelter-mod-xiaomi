<?php

namespace inisire\Xiaomi\Module\Configuration;

class Device
{
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly array  $parameters
    )
    {
    }
}