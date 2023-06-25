<?php


namespace Shelter\Module\Xiaomi\Service;


use BinSoul\Net\Mqtt\Client\React\ReactMqttClient;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class MqttConnectionWatchdog
{
    private LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    private function reconnect(ReactMqttClient $mqttClient)
    {
        $this->loop->addPeriodicTimer(5, function (TimerInterface $timer) use ($mqttClient) {
            if (!$mqttClient->isConnected()) {
                $mqttClient->connect($mqttClient->getHost());
            } else {
                $this->loop->cancelTimer($timer);
            }
        });
    }

    public function run(ReactMqttClient $mqttClient)
    {
        $mqttClient->on('connected', function () {

        });

        $mqttClient->on('disconnect', function () use ($mqttClient) {
            $this->reconnect($mqttClient);
        });

        $mqttClient->on('close', function () use ($mqttClient) {
            $this->reconnect($mqttClient);
        });
    }
}