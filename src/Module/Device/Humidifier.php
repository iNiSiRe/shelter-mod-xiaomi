<?php

namespace inisire\Xiaomi\Module\Device;

use inisire\NetBus\Query\QueryInterface;
use inisire\NetBus\Query\Result;
use inisire\NetBus\Query\ResultInterface;
use inisire\Xiaomi\Module\Device;
use Psr\Log\LoggerInterface;
use function inisire\fibers\asleep;
use function inisire\fibers\async;

class Humidifier extends Device
{
    private \inisire\Xiaomi\Core\Device\Humidifier $humidifier;

    function load(): void
    {
        $this->humidifier = new \inisire\Xiaomi\Core\Device\Humidifier(
            $this->getParameter('host'),
            $this->getParameter('token'),
        );

        async(function () {
            while (true) {
                $state = $this->humidifier->getState();

                if ($state === false) {
                    asleep(30.0);
                    continue;
                }

                $this->properties->update([
                    'enabled' => $state['enabled'],
                    'mode' => match ($state['mode']) {
                        'low' => 1,
                        'medium' => 2,
                        'high' => 3
                    },
                    'humidity' => $state['humidity'],
                    'temperature' => $state['temperature'],
                    'water_level' => $state['water_level']
                ]);
                asleep(60.0);
            }
        });
    }

    public function enable(): ResultInterface
    {
        $response = $this->humidifier->enable();

        return new Result(0, ['result' => $response]);
    }

    public function disable(): ResultInterface
    {
        $response = $this->humidifier->disable();

        return new Result(0, ['result' => $response]);
    }

    public function getState(): ResultInterface
    {
        $response = $this->humidifier->disable();

        return new Result(0, ['result' => $response]);
    }

    public function onQuery(QueryInterface $query): ResultInterface
    {
        return match ($query->getName()) {
            'Enable' => $this->enable(),
            'Disable' => $this->disable(),
            'GetState' => $this->getState(),
            default => parent::onQuery($query)
        };
    }

    public function setLogger(LoggerInterface $logger): void
    {
        parent::setLogger($logger);
        $this->humidifier->setLogger($logger);
    }
}