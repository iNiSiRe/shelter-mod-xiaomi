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

        $gateway = $this->getParameter('gateway');

        $this->subscribe(new CallableSubscription(
            new Matches([$gateway]),
            new Matches(['Gateway.SubDeviceUpdate']),
            function (EventInterface $event) {
                $data = $event->getData();
                $did = $data['did'];
                if ($did === $this->getDid()) {
                    $this->handleSubdeviceUpdate($event);
                }
            }
        ));
    }

    public function getDid(): string
    {
        return $this->getParameter('did');
    }

    public function getSubscribedQueries(): array
    {
        return [];
    }

    public function handleSubdeviceUpdate(EventInterface $event): void
    {
        $data = $event->getData();
        $properties = $data['properties'];

        $properties = $this->converter->convert($this->getModel(), $properties);

        $this->properties->update($properties);

        $this->dispatch(new DeviceUpdateEvent($this->getId(), $properties));
    }
}