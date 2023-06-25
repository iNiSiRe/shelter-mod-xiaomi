<?php


namespace Shelter\Module\Xiaomi\Service;


class PropertiesConverter
{
    const COMMON = [
        '3.1.85' => 'state',
        '8.0.2001' => 'battery',
        '8.0.2008' => ['battery_voltage', self::FORMAT_DIVIDE, 1000],
    ];

    const FORMAT_DIVIDE = 'div';

    const MAP = [
        'lumi.remote.b286acn01' => [
            '13.1.85' => 'left_button',
            '13.2.85' => 'right_button',
            '13.5.85' => 'double_button'
        ],
        'lumi.sensor_wleak.aq1' => [
            '3.1.85' => 'state',
        ],
        'lumi.sensor_magnet.aq2' => [
            '3.1.85' => 'state',
        ],
        'lumi.sensor_motion.aq2' => [
            '3.1.85' => 'state',
            '0.3.85' => '_illuminance',
            '0.4.85' => 'illuminance',
        ],
        'lumi.sensor_ht' => [
            '0.1.85' => ['temperature', self::FORMAT_DIVIDE, 100],
            '0.2.85' => ['humidity', self::FORMAT_DIVIDE, 100],
            '0.3.85' => ['pressure', self::FORMAT_DIVIDE, 100],
        ]
    ];

    private function getMap(string $model): array
    {
        return (self::MAP[$model] ?? []) + self::COMMON;
    }

    public function format(string $format, mixed $value, mixed $param): mixed
    {
        if ($format == self::FORMAT_DIVIDE) {
            return round($value / $param, 1);
        } else {
            return $value;
        }
    }

    public function convert(string $model, array $properties)
    {
        $map = $this->getMap($model);
        $converted = [];

        foreach ($properties as $key => $value) {
            $specification = $map[$key] ?? null;

            if (!$specification) {
                continue;
            }

            $specification = (array) $specification;

            $name = $specification[0];
            $format = $specification[1] ?? null;
            $param = $specification[2] ?? null;

            if ($format) {
                $converted[$name] = $this->format($format, $value, $param);
            } else {
                $converted[$name] = $value;
            }
        }

        return $converted;
    }
}