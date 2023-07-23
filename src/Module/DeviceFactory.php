<?php

namespace inisire\Xiaomi\Module;

use inisire\NetBus\Event\EventBusInterface;
use inisire\Xiaomi\Module\Device\Gateway;
use inisire\Xiaomi\Module\Device\Humidifier;
use inisire\Xiaomi\Module\Device\SubDevice;
use Psr\Log\LoggerInterface;


class DeviceFactory
{
    public function __construct(
        private readonly LoggerInterface   $logger,
        private readonly EventBusInterface $eventBus
    )
    {
    }

    public function createByModel(string $model, string $id, array $parameters): Device
    {
        $device = match ($model) {
            'lumi.gateway.mgl03' => new Gateway($id, $model, 'generic', $parameters, $this->eventBus),
            'zhimi.humidifier.ca1' => new Humidifier($id, $model, 'generic.humidifier', $parameters, $this->eventBus),
            'lumi.sensor_ht' => new SubDevice($id, $model, 'sensor.humidity_temperature', $parameters, $this->eventBus),
            'lumi.sensor_motion.aq2' => new SubDevice($id, $model, 'sensor.motion', $parameters, $this->eventBus),
            'lumi.sensor_magnet.aq2' => new SubDevice($id, $model, 'sensor.magnet', $parameters, $this->eventBus),
            default => throw new \RuntimeException(sprintf('Model "%s" not supported', $model))
        };

        $device->setLogger($this->logger);

        return $device;
    }
}