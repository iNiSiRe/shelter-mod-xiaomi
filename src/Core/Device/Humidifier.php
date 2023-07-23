<?php

namespace inisire\Xiaomi\Core\Device;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Available properties: ["power", "mode", "temp_dec", "buzzer", "led_b", "child_lock", "humidity", "depth", "dry"]
 */
class Humidifier extends GenericDevice
{
    public function enable(): bool
    {
        $response = $this->call('set_power', ['on']);

        if ($response['result'][0] === 'ok') {
            return true;
        }

        return false;
    }

    public function disable(): bool
    {
        $response = $this->call('set_power', ['off']);

        if ($response['result'][0] === 'ok') {
            return true;
        }

        return false;
    }

    #[ArrayShape([
        'enabled' => 'bool',
        'mode' => 'string',
        'temperature' => 'float',
        'humidity' => 'int',
        'water_level' => 'int'
    ])]
    public function getState(): false|array
    {
        $response = $this->call('get_prop', ["power", "mode", "temp_dec", "humidity", "depth"]);

        if (count($response['result']) !== 5) {
            return false;
        }

        return [
            'enabled' => $response['result'][0] === 'on',
            'mode' => $response['result'][1],
            'temperature' => (float) ($response['result'][2] / 10),
            'humidity' => (int) $response['result'][3],
            'water_level' => min(100.0, round((int) $response['result'][4] / 120 * 100)),
        ];
    }
}