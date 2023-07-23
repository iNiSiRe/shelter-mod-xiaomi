<?php

namespace inisire\Xiaomi\Core\Gateway;

use inisire\fibers\Network\SocketFactory;
use inisire\Protocol\MiIO\Connection;
use inisire\Protocol\MiIO\Device;
use inisire\Protocol\MiIO\Response;
use Psr\Log\LoggerInterface;
use inisire\Xiaomi\Core\Device\GenericDevice;
use inisire\Xiaomi\Core\Gateway\SubDevice;

class XiaomiGateway extends GenericDevice
{
    public function __construct(
        private readonly string          $host,
        private readonly string          $token,
        private readonly string          $did,
    )
    {
        parent::__construct($host, $token);
    }

    /**
     * @return SubDevice[]
     */
    public function getSubDevices(): array
    {
        $response = $this->call('get_device_list');

        $resultCode = $response['code'] ?? null;

        if ($resultCode !== 0) {
            $this->logger->error('Call "get_device_list" return an error', ['result' => $response]);
            return [];
        }

        $devices = [];

        foreach ($response['result'] ?? [] as $device) {
            $devices[] = new SubDevice($device['model'], $device['did'], []);
        }

        return $devices;
    }

    public function triggerAlarm(): array
    {
        return $this->call('set_properties', [[
            'did' => $this->did,
            'siid' => 3,
            'piid' => 22,
            'value' => 1,
        ]]);
    }

    public function disarmAlarm(): array
    {
        return $this->call('set_properties', [[
            'did' => $this->did,
            'siid' => 3,
            'piid' => 22,
            'value' => 0,
        ]]);
    }

    public function getInfo(): array
    {
        return $this->call('miIO.info', []);
    }

    public function getHost(): string
    {
        return $this->host;
    }
}