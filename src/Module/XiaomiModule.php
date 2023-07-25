<?php

namespace inisire\Xiaomi\Module;

use inisire\Logging\EchoLogger;
use inisire\mqtt\NetBus\QueryBus;
use inisire\NetBus\Event\CallableSubscription;
use inisire\NetBus\Event\EventBusInterface;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\NetBus\Event\Subscription\Wildcard;
use inisire\NetBus\Query\QueryBusInterface;
use inisire\NetBus\Query\QueryHandlerInterface;
use inisire\NetBus\Query\QueryInterface;
use inisire\NetBus\Query\Result;
use inisire\NetBus\Query\ResultInterface;
use inisire\NetBus\Query\Route;
use inisire\Xiaomi\Module\Device\SubDevice;
use Shelter\Bus\Event\DiscoverResponse;
use Shelter\Bus\Events;

class XiaomiModule
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
        private readonly QueryBus          $queryBus
    )
    {
        $this->startedAt = time();

        $logger = new EchoLogger();

        $factory = new DeviceFactory($logger, $this->eventBus);

        foreach ($this->configuration->getDevices() as $device) {
            $this->devices[] = $factory->createByModel($device->model, $device->id, $device->parameters);
        }

        $this->queryBus->on($this->getBusId(), [$this, 'onQuery']);

        $this->eventBus->subscribe(new CallableSubscription(
            new Wildcard(),
            new Matches([Events::DISCOVER_REQUEST]),
            function (EventInterface $event) {
                foreach ($this->devices as $device) {
                    $this->eventBus->dispatch($this->getBusId(), new DiscoverResponse($device->getId(), $device->getDiscoverModel(), $device->getProperties()));
                }
            }
        ));

        $this->eventBus->subscribe(new CallableSubscription(
            new Wildcard(),
            new Matches(['Gateway.Zigbee.Report', 'Gateway.Zigbee.Heartbeat']),
            function (EventInterface $event) {
                $data = $event->getData();
                $did = $data['did'];
                foreach ($this->devices as $device) {
                    if ($device instanceof SubDevice && $device->getDid() === $did) {
                        match ($event->getName()) {
                            'Gateway.Zigbee.Report' => $device->handleZigbeeReport($data['properties']),
                            'Gateway.Zigbee.Heartbeat' => $device->handleZigbeeHeartbeat($data['properties']),
                        };
                        break;
                    }
                }
            }
        ));

        foreach ($this->devices as $device) {
            $this->queryBus->on($device->getId(), [$device, 'onQuery']);
        }
    }

    public function onQuery(QueryInterface $query): ResultInterface
    {
        return match ($query->getName()) {
            'Status' => new Result(0, [
                'memory' => [
                    'usage' => round(memory_get_usage() / 1024 / 1024, 2),
                    'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2)
                ],
                'uptime' => time() - $this->startedAt
            ]),
            default => new Result(-1, ['error' => 'Bad query name'])
        };
    }

    public function getBusId(): string
    {
        return $this->busId;
    }
}