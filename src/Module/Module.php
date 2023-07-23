<?php

namespace inisire\Xiaomi\Module;

use inisire\NetBus\Query\QueryHandlerInterface;

interface Module extends QueryHandlerInterface
{
    public function getBusId(): string;

    public function load(): void;
}