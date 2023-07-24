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
    public function handleSubdeviceUpdate(EventInterface $event): void
    {
//        '3.1.85' => 'motion',
//        '0.3.85' => '_illuminance',
//        '0.4.85' => 'illuminance',

        $data = $event->getData();
        $properties = $data['properties'] ?? [];

        $update = [];

        if (($properties['3.1.85'] ?? null) !== null) {
            $update['motionAt'] = time();
        }

        if (($illuminance = ($properties['0.4.85'] ?? null)) !== null) {
            $update['illuminance'] = $illuminance;
        }


        if (($battery = ($properties['8.0.2008'] ?? null)) !== null) {
            $update['battery_voltage'] = round($battery / 1000, 2);
        }

        if (!$update) {
            return;
        }

        $this->properties->update($update);

        $this->dispatch(new DeviceUpdateEvent($this->getId(), $update));
    }
}