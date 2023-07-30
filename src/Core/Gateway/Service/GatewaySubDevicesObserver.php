<?php

namespace inisire\Xiaomi\Core\Gateway\Service;

use Evenement\EventEmitterTrait;
use inisire\fibers\Network\SocketFactory;
use inisire\Logging\NullLogger;
use inisire\mqtt\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use BinSoul\Net\Mqtt as MQTT;

class GatewaySubDevicesObserver implements LoggerAwareInterface
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
        $mqtt = new Connection();
        $mqtt->onMessage([$this, 'handleMessage']);

        do {
            $connected = $mqtt->connect($this->host);
        } while (!$connected);

        $mqtt->onDisconnect([$this, 'onDisconnect']);

        $this->logger->info('GatewaySubDevicesObserver: connected');
        $mqtt->subscribe(new MQTT\DefaultSubscription('zigbee/send'));
    }

    public function onDisconnect(): void
    {
        $this->logger->error('GatewaySubDevicesObserver: disconnected');
        $this->connect();
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
                        $report = [
                            'did' => $message['did'],
                            'properties' => $this->prepareParameters($message['params'])
                        ];
                        $this->emit('report', [$report]);
                        break;
                    }

                    case 'heartbeat':
                    {
                        $updates = $message['params'] ?? [];
                        foreach ($updates as $update) {
                            $heartbeat = [
                                'did' => $update['did'],
                                'properties' => $this->prepareParameters($update['res_list'])
                            ];
                            $this->emit('heartbeat', [$heartbeat]);
                        }
                        break;
                    }
                }
                break;
            }
        }
    }

    public function onReport(callable $handler): void
    {
        $this->on('report', $handler);
    }

    public function onHeartbeat(callable $handler): void
    {
        $this->on('heartbeat', $handler);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}