<?php

namespace inisire\Xiaomi\Module\Device;

use Evenement\EventEmitterTrait;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\Xiaomi\Module\Device;
use inisire\Xiaomi\Module\Service\PropertiesConverter;
use Shelter\Bus\Event\DeviceUpdateEvent;


class MotionSensor extends SubDevice
{
    private function normalizeMotionAt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return $value === 1
            ? time()
            : null;
    }

    private function normalizeIlluminance(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return $value;
    }

    public function handleZigbeeReport(array $properties): void
    {
//        '3.1.85' => 'motion',
//        '0.3.85' => '_illuminance',
//        '0.4.85' => 'illuminance',

        $update = $this->filter([
            'motionAt' => $this->normalizeMotionAt($properties['3.1.85'] ?? null),
            'illuminance' => $this->normalizeIlluminance($properties['0.4.85'] ?? null)
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