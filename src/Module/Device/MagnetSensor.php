<?php

namespace inisire\Xiaomi\Module\Device;

use Evenement\EventEmitterTrait;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\Xiaomi\Module\Device;
use inisire\Xiaomi\Module\Service\PropertiesConverter;
use Shelter\Bus\Event\DeviceUpdateEvent;


class MagnetSensor extends SubDevice
{
    private function normalizeOpen(mixed $value): ?bool
    {
        if (!is_numeric($value)) {
            return null;
        }

        return $value === 1;
    }

    public function handleZigbeeReport(array $properties): void
    {
//        '3.1.85' => 'state',

        $update = $this->filter([
            'open' => $this->normalizeOpen($properties['3.1.85'] ?? null)
        ]);

        $changes = $this->properties->update($update);

        if (!$changes->isEmpty()) {
            $this->dispatch(new DeviceUpdateEvent($this->getId(), $changes->all()));
        }
    }

    public function handleZigbeeHeartbeat(array $properties): void
    {
        $update = $this->filter([
            'battery_voltage' => $this->normalizeBatteryVoltage($properties['8.0.2008'])
        ]);

        $changes = $this->properties->update($update);

        if (!$changes->isEmpty()) {
            $this->dispatch(new DeviceUpdateEvent($this->getId(), $changes->all()));
        }
    }
}