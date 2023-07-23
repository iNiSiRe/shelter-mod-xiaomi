<?php

namespace inisire\Xiaomi\Core\Device;

class Vacuum extends GenericDevice
{
    public function start(): void
    {
        $this->call('app_start');
    }

    public function stop(): void
    {
        $this->call('app_stop');
    }

    public function pause(): void
    {
        $this->call('app_pause');
    }

    public function charge(): void
    {
        $this->call('app_charge');
    }
}