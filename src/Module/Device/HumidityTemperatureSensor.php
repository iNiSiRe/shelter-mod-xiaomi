<?php

namespace inisire\Xiaomi\Module\Device;

use Evenement\EventEmitterTrait;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\Xiaomi\Module\Device;
use inisire\Xiaomi\Module\Service\PropertiesConverter;
use Shelter\Bus\Event\DeviceUpdateEvent;


class HumidityTemperatureSensor extends SubDevice
{
    private function normalize(string $type, mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        // Filter corrupted reports
        if ($type === 'temperature' && $value < -50) {
            return null;
        } else if ($type === 'humidity' && $value > 1000) {
            return null;
        }

        return round($value / 100, 2);
    }

    public function handleZigbeeReport(array $properties): void
    {
//        '0.1.85' => ['temperature', self::FORMAT_DIVIDE, 100],
//        '0.2.85' => ['humidity', self::FORMAT_DIVIDE, 100],
//        '0.3.85' => ['pressure', self::FORMAT_DIVIDE, 100],

        $update = $this->filter([
            'temperature' => $this->normalize('temperature',$properties['0.1.85'] ?? null),
            'humidity' => $this->normalize('humidity', $properties['0.2.85'] ?? null),
            'pressure' => $this->normalize('pressure', $properties['0.3.85'] ?? null),
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