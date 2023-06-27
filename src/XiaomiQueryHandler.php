<?php

namespace Shelter\Module\Xiaomi;

use inisire\NetBus\DTO\PromiseResult;
use inisire\NetBus\DTO\Result;
use inisire\NetBus\Query;
use inisire\NetBus\QueryHandler;
use inisire\NetBus\QueryInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Async\await;

class XiaomiQueryHandler implements QueryHandler
{
    private readonly int $startedAt;

    public function __construct(
        private readonly XiaomiGatewayClient $gateway
    )
    {
        $this->startedAt = time();
    }

    public function getSupportedQueries(): array
    {
        return [
            'Module.GetStatus',
            'Gateway.GetSubDevices',
            'Gateway.Miio.GetInfo',
            'Gateway.Miio.TriggerAlarm',
            'Gateway.Miio.DisarmAlarm'
        ];
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

    public function getSubDevices(): Query\ResultInterface
    {
        $devices = $this->gateway->getSubDevices();

        $data = [];
        foreach ($devices as $device) {
            $data[] = ['did' => $device->did, 'model' => $device->model, 'props' => $device->properties];
        }

        return new Result(0, $data);
    }

    public function getInfo(): PromiseInterface
    {
        return $this->gateway->getInfo()
            ->then(function ($info) {
                return new Result(0, $info);
            });
    }

    public function setArming(bool $on): PromiseInterface
    {
        return $this->gateway->setArming($on)
            ->then(function (array $result) {
                return new Result(0, $result);
            });
    }

    public function handleQuery(QueryInterface $query): Query\ResultInterface|PromiseInterface
    {
        return match ($query->getName()) {
            'Module.GetStatus' => $this->getStatus(),
            'Gateway.GetSubDevices' => $this->getSubDevices(),
            'Gateway.Miio.GetInfo' => $this->getInfo(),
            'Gateway.Miio.TriggerAlarm' => $this->triggerAlarm(),
            'Gateway.Miio.DisarmAlarm' => $this->disarmAlarm(),
            'Gateway.Miio.SetArmingOn' => $this->gateway->setArming(true),
            'Gateway.Miio.SetArmingOff' => $this->gateway->setArming(false),
            default => new Result(-1, ['error' => 'Bad query'])
        };
    }
}