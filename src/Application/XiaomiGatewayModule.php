<?php

namespace Shelter\Module\Xiaomi\Application;

use inisire\NetBus\Event\Event;
use inisire\NetBus\Event\EventBusClient;
use inisire\NetBus\Event\EventBusInterface;
use inisire\NetBus\Query\QueryHandlerInterface;
use inisire\NetBus\Query\QueryInterface;
use inisire\NetBus\Query\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use React\Promise\PromiseInterface;
use Shelter\Module\Xiaomi\Application\Event\DeviceUpdateEvent;
use Shelter\Module\Xiaomi\Core\Event\GatewaySubDeviceUpdate;
use Shelter\Module\Xiaomi\Core\Gateway\XiaomiGatewayClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use inisire\NetBus\Query;
use function React\Promise\resolve;

class XiaomiGatewayModule implements EventSubscriberInterface, QueryHandlerInterface
{
    private readonly int $startedAt;

    public function __construct(
        private readonly XiaomiGatewayClient $gateway,
        private readonly EventBusInterface   $eventBus,
        private readonly Configuration       $configuration
    )
    {
        $this->startedAt = time();
    }

    public static function getSubscribedEvents()
    {
        return [
            GatewaySubDeviceUpdate::class => 'onSubdeviceUpdate'
        ];
    }

    public function onSubdeviceUpdate(GatewaySubDeviceUpdate $event): void
    {
        $nodeId = $this->configuration->getNodeId();
        $deviceId = $this->configuration->getDeviceIdByDid($event->getDid());

        $this->eventBus->dispatch($nodeId, new Event('Gateway.SubDeviceUpdate', ['did' => $event->getDid(), 'properties' => $event->getProperties()]));
        $this->eventBus->dispatch($nodeId, new DeviceUpdateEvent($deviceId, $event->getProperties()));
    }

    public function getHandlers(): array
    {
        return [
            'Module.GetStatus' => [$this, 'getStatus'],
            'Gateway.GetInfo' => [$this, 'getInfo'],
            'Gateway.TriggerAlarm' => [$this, 'triggerAlarm'],
            'Gateway.DisarmAlarm' => [$this, 'disarmAlarm'],
            'Gateway.Miio.Call' => [$this, 'call'],
        ];
    }

    public function getSupportedQueries(): array
    {
        return array_keys($this->getHandlers());
    }

    public function getStatus(): Query\ResultInterface
    {
        return new Result(0, [
            'memory' => [
                'usage' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2)
            ],
            'uptime' => time() - $this->startedAt
        ]);
    }

    public function triggerAlarm(): PromiseInterface
    {
        return $this->gateway->triggerAlarm()
            ->then(function ($result) {
                return new Result(0, $result);
            });
    }

    public function disarmAlarm(): PromiseInterface
    {
        return $this->gateway->disarmAlarm()
            ->then(function ($result) {
                return new Result(0, $result);
            });
    }

    public function getInfo(): PromiseInterface
    {
        return $this->gateway->getInfo()
            ->then(function ($info) {
                return new Result(0, $info);
            });
    }

    public function call(QueryInterface $query): PromiseInterface
    {
        $data = $query->getData();
        $method = $data['method'] ?? null;
        $params = $data['params'] ?? [];

        if (!$method) {
            return resolve(new Result(-1, ['error' => 'Parameter "method" is required']));
        }

        return $this->gateway->call($method, $params)
            ->then(function (array $result) {
                return new Result(0, $result);
            });
    }

    public function handleQuery(QueryInterface $query): PromiseInterface
    {
        $handler = $this->getHandlers()[$query->getName()] ?? null;

        if (!$handler) {
            return resolve(new Result(-1, ['error' => 'Not found']));
        }

        return resolve(call_user_func($handler, $query));
    }
}