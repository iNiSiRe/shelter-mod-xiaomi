<?php

namespace Shelter\Module\Xiaomi\Application;

use Shelter\Module\Xiaomi\Core\Gateway\SubDevice;
use Shelter\Module\Xiaomi\Core\Gateway\XiaomiGateway;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    private array $config = [];

    public function __construct(
        private readonly string $path
    )
    {
        $this->config = Yaml::parse(file_get_contents($this->path));
    }

    public function getGateway(): XiaomiGateway
    {
        $subDevices = [];
        foreach ($this->config['gateway']['subdevices'] ?? [] as $device) {
            $subDevices[$device['did']] = new SubDevice($device['model'], $device['did']);
        }

        $did = $this->config['gateway']['did'] ?? null;
        $host = $this->config['gateway']['host'] ?? null;
        $token = $this->config['gateway']['token'] ?? null;

        return new XiaomiGateway($did, $host, $token, $subDevices);
    }

    public function getNodeId(): string
    {
        return $this->config['bus']['nodeId'] ?? 'gateway';
    }

    public function getDeviceIdByDid(string $did): ?string
    {
        foreach ($this->config['gateway']['subdevices'] ?? [] as $device) {
            if ($device['did'] === $did) {
                return $device['deviceId'];
            }
        }

        return null;
    }

    public function getQueryBusBind(): string
    {
        return $this->config['bus']['query']['bind'] ?? '0.0.0.0:5555';
    }

    public function getEventBusBind(): string
    {
        return $this->config['bus']['event']['bind'] ?? '0.0.0.0:5556';
    }
}