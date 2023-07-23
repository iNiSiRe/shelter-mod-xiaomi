<?php

namespace inisire\Xiaomi\Module;

use inisire\Xiaomi\Module\Configuration\Device;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    public function __construct(
        private readonly array $tree
    )
    {
    }

    /**
     * @return iterable<Device>
     */
    public function getDevices(): iterable
    {
        foreach ($this->tree['devices'] as $device) {
            yield new Device($device['id'], $device['model'], $device['parameters']);
        }
    }

    public static function fromYaml(string $path): static
    {
        return new self(Yaml::parse(file_get_contents($path)));
    }
}