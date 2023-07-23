<?php

namespace inisire\Xiaomi\Core\Gateway\Service;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use inisire\fibers\Network\SocketFactory;
use inisire\Logging\NullLogger;
use inisire\mqtt\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use BinSoul\Net\Mqtt as MQTT;
use inisire\Xiaomi\Core\Gateway\Event\GatewaySubDeviceUpdate;

class GatewaySubDevicesObserver implements EventEmitterInterface, LoggerAwareInterface
{
    use EventEmitterTrait;

    private LoggerInterface $logger;

    public function __construct(
        private readonly string $host,
    )
    {
        $this->logger = new NullLogger();
    }

    private function prepareParameters(array $rawParams)
    {
        $extractedParameters = [];
        foreach ($rawParams as $param) {
            $extractedParameters[$param['res_name']] = $param['value'];
        }

        return $extractedParameters;
    }

    public function connect(): void
    {
        $mqtt = new Connection($this->logger, new SocketFactory());

        if (!$mqtt->connect($this->host)) {
            return;
        }

        $this->logger->info('MQTT: Connected', ['channel' => __CLASS__]);
        $mqtt->subscribe(new MQTT\DefaultSubscription('zigbee/send'));
        $mqtt->onMessage([$this, 'handleMessage']);
    }

    public function handleMessage(MQTT\Message $message): void
    {
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
    }

    private function updateSubDevice(string $did, array $params = [])
    {
        $this->emit('update', [new GatewaySubDeviceUpdate($did, $params)]);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}