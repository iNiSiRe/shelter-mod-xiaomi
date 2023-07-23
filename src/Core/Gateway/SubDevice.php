<?php

namespace inisire\Xiaomi\Core\Gateway;

class SubDevice
{
    public function __construct(
        private readonly string $model,
        private readonly string $did,
        private array           $properties = []
    )
    {
    }

    public function getDid(): string
    {
        return $this->did;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function updateProperties(array $properties): void
    {
        $this->properties = array_merge($this->properties, $properties);
    }
}