<?php

namespace Shelter\Module\Xiaomi;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Shelter\Module\Xiaomi as Xiaomi;
use BinSoul\Net\Mqtt as MQTT;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectorInterface;
use Shelter\Module\Xiaomi\Event\GatewaySubDeviceUpdate;

class XiaomiGatewayClient
{
    private const ALARM_DISARMED_STATUS = 0;
    private const ALARM_TRIGGERED_STATUS = 1;

    private ?XiaomiGateway $gateway = null;

    public function __construct(
        private readonly LoggerInterface                    $logger,
        private readonly ConnectorInterface                 $connector,
        private readonly LoopInterface                      $loop,
        private readonly Xiaomi\Service\MiioClient          $miioClient,
        private readonly Xiaomi\Service\PropertiesConverter $converter,
        private readonly EventDispatcherInterface           $eventBus
    )
    {
    }

    private function prepareParameters(array $rawParams)
    {
        $extractedParameters = [];
        foreach ($rawParams as $param) {
            $extractedParameters[$param['res_name']] = $param['value'];
        }

        return $extractedParameters;
    }

    public function connect(XiaomiGateway $gateway): void
    {
        $this->gateway = $gateway;

        $mqtt = new MQTT\Client\React\ReactMqttClient($this->connector, $this->loop);

        $mqtt->on('connect', function () use ($mqtt) {
            $this->logger->info('MQTT: Connected', ['channel' => __CLASS__]);
            $mqtt->subscribe(new MQTT\DefaultSubscription("#"));
        });

        $mqtt->on('disconnect', function () {
            $this->logger->info('MQTT: Disconnected', ['channel' => __CLASS__]);
        });

        $mqtt->on('message', function (MQTT\Message $message) {
            $this->logger->debug('MQTT: Received message', [
                'topic' => $message->getTopic(),
                'message' => $message->getPayload()
            ]);

            switch ($message->getTopic()) {
                case 'zigbee/send':
                {
                    $message = json_decode($message->getPayload(), true);
                    $cmd = $message['cmd'] ?? null;

                    // topic="zigbee/send"
                    // message="{"cmd":"report","id":2000000385,"did":"lumi.158d00053e9751","time":1607819249617,
                    //           "rssi":-34,"zseq":252,"params":[{"res_name":"3.1.85","value":1}],"dev_src":"0"}"
                    switch ($cmd) {
                        case 'report':
                        {
                            $this->updateSubDevice($message['did'], $this->prepareParameters($message['params']));
                            break;
                        }

                        case 'heartbeat':
                        {
                            $updates = $message['params'] ?? [];
                            foreach ($updates as $update) {
                                $this->updateSubDevice($update['did'], $this->prepareParameters($update['res_list']));
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        });

        $mqtt->on('error', function (\Exception $exception) {
            $this->logger->error('MQTT client error', ['channel' => __CLASS__, 'error' => $exception->getMessage()]);
        });

        $mqtt->on('close', function () {
            $this->logger->info('MQTT client close');
        });

        $mqtt->connect($gateway->host);

        $watchdog = new Xiaomi\Service\MqttConnectionWatchdog($this->loop);
        $watchdog->run($mqtt);
    }

    public function setAlarm(int $value)
    {
        return $this
            ->miioClient
            ->call($this->gateway, 'set_properties', [
                'did' => $this->gateway->did,
                'siid' => 3,
                'piid' => 22,
                'value' => $value,
            ])
            ->then(function ($response) {});
    }

    public function updateSubDevice(string $did, array $params = [])
    {
        $device = $this->gateway->subDevices[$did] ?? null;

        if (!$device) {
            $this->logger->error('Update subdevice error', [
                'did' => $did,
                'error' => 'Subdevice not exists'
            ]);
            return;
        }

        $convertedParams = $this->converter->convert($device->model, $params);

        if (!$convertedParams) {
            return;
        }

        $convertedParams['updated_at'] = time();

        foreach ($convertedParams as $name => $value) {
            $device->properties[$name] = $value;
        }

        $this->eventBus->dispatch(new GatewaySubDeviceUpdate($device->did, $convertedParams));
    }

    /**
     * @return array<SubDevice>
     */
    public function getSubDevices(): array
    {
        return $this->gateway->subDevices;
    }

    public function triggerAlarm()
    {
        $this->setAlarm(self::ALARM_TRIGGERED_STATUS);
    }

    public function disarmAlarm()
    {
        $this->setAlarm(self::ALARM_DISARMED_STATUS);
    }

    public function getInfo(): PromiseInterface
    {
        return $this->miioClient->call($this->gateway, 'miIO.info', []);
    }
}