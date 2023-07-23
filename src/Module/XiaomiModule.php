<?php

namespace inisire\Xiaomi\Module;

use inisire\Logging\EchoLogger;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventBusInterface;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\NetBus\Event\Subscription\Wildcard;
use inisire\NetBus\Query\QueryBusInterface;
use inisire\NetBus\Query\QueryHandlerInterface;
use inisire\NetBus\Query\Result;
use inisire\NetBus\Query\ResultInterface;
use Shelter\Bus\Event\DiscoverResponse;
use Shelter\Bus\Events;

class XiaomiModule implements QueryHandlerInterface
{
    private readonly int $startedAt;

    /**
     * @var array<Device>
     */
    private array $devices = [];

    public function __construct(
        private readonly string            $busId,
        private readonly Configuration     $configuration,
        private readonly EventBusInterface $eventBus,
        private readonly QueryBusInterface $queryBus
    )
    {
        $this->startedAt = time();

        $logger = new EchoLogger();

        $factory = new DeviceFactory($logger, $this->eventBus);

        foreach ($this->configuration->getDevices() as $device) {
            $this->devices[] = $factory->createByModel($device->model, $device->id, $device->parameters);
        }

        $this->eventBus->subscribe(new CallableSubscription(
            new Wildcard(),
            new Matches([Events::DISCOVER_REQUEST]),
            function (EventInterface $event) {
                foreach ($this->devices as $device) {
                    $this->eventBus->dispatch($this->getBusId(), new DiscoverResponse($device->getId(), $device->getDiscoverModel(), $device->getProperties()));
                }
            }
        ));

        foreach ($this->devices as $device) {
            $this->queryBus->registerHandler($device->getId(), $device);
        }
    }

    public function getStatus(): ResultInterface
    {
        return new Result(0, [
            'memory' => [
                'usage' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2)
            ],
            'uptime' => time() - $this->startedAt
        ]);
    }

    public function getSubscribedQueries(): array
    {
        return [
            'GetStatus' => [$this, 'getStatus'],
        ];
    }

    public function getBusId(): string
    {
        return $this->busId;
    }
}