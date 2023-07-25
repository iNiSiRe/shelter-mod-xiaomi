<?php

namespace inisire\Xiaomi\Module\Device;

use inisire\NetBus\Event\Event;
use inisire\Xiaomi\Core\Gateway\Service\GatewaySubDevicesObserver;
use inisire\Xiaomi\Core\Gateway\XiaomiGateway;
use inisire\NetBus\Query\QueryInterface;
use inisire\NetBus\Query\Result;
use inisire\NetBus\Query\ResultInterface;
use inisire\Xiaomi\Module\Device;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Gateway extends Device implements LoggerAwareInterface
{
    private XiaomiGateway $gateway;

    private GatewaySubDevicesObserver $observer;

    public function load(): void
    {
        $this->gateway = new XiaomiGateway(
            $this->getParameter('host'),
            $this->getParameter('token'),
            $this->getParameter('did')
        );

        $this->observer = new GatewaySubDevicesObserver(
            $this->getParameter('host')
        );

        $this->observer->connect();

        $this->observer->onReport(function (array $data) {
            $this->dispatch(new Event('Gateway.Zigbee.Report', $data));
        });

        $this->observer->onHeartbeat(function (array $data) {
            $this->dispatch(new Event('Gateway.Zigbee.Heartbeat', $data));
        });
    }

    public function onQuery(QueryInterface $query): ResultInterface
    {
        return match ($query->getName()) {
            'GetInfo' => $this->getInfo(),
            'TriggerAlarm' => $this->triggerAlarm(),
            'DisarmAlarm' => $this->disarmAlarm(),
            'GetSubDevices' => $this->getSubDevices(),
            'Call' => $this->call($query),
            default => parent::onQuery($query)
        };
    }

    public function triggerAlarm(): ResultInterface
    {
        $response = $this->gateway->triggerAlarm();

        return new Result(0, $response);
    }

    public function disarmAlarm(): ResultInterface
    {
        $response = $this->gateway->disarmAlarm();

        return new Result(0, $response);
    }

    public function getInfo(): ResultInterface
    {
        $response = $this->gateway->getInfo();

        return new Result(0, $response);
    }

    public function getSubDevices(): ResultInterface
    {
        $response = $this->gateway->getSubDevices();

        return new Result(0, $response);
    }

    public function call(QueryInterface $query): ResultInterface
    {
        $data = $query->getData();
        $method = $data['method'] ?? null;
        $params = $data['params'] ?? [];

        if (!$method) {
            return new Result(-1, ['error' => 'Parameter "method" is required']);
        }

        $response = $this->gateway->call($method, $params);

        return new Result(0, $response);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->gateway->setLogger($logger);
        $this->observer->setLogger($logger);
    }
}