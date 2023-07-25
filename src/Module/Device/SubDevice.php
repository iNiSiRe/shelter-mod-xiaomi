<?php

namespace inisire\Xiaomi\Module\Device;

use Evenement\EventEmitterTrait;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\Xiaomi\Module\Device;
use inisire\Xiaomi\Module\Service\PropertiesConverter;
use Shelter\Bus\Event\DeviceUpdateEvent;


class SubDevice extends Device
{
    use EventEmitterTrait;

    private readonly PropertiesConverter $converter;

    public function load(): void
    {
        $this->converter = new PropertiesConverter();
    }

    public function getDid(): string
    {
        return $this->getParameter('did');
    }

    public function getSubscribedQueries(): array
    {
        return [];
    }

    public function handleZigbeeReport(array $properties): void
    {
        $properties = $this->converter->convert($this->getModel(), $properties);

        $changes = $this->properties->update($properties);

        if (!$changes->isEmpty()) {
            $this->dispatch(new DeviceUpdateEvent($this->getId(), $changes->all()));
        }
    }

    public function handleZigbeeHeartbeat(array $properties): void
    {
        $properties = $this->converter->convert($this->getModel(), $properties);

        $changes = $this->properties->update($properties);

        if (!$changes->isEmpty()) {
            $this->dispatch(new DeviceUpdateEvent($this->getId(), $changes->all()));
        }
    }

    protected function filter(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if ($value !== null) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    protected function normalizeBatteryVoltage(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        return round($value / 1000, 2);
    }
}