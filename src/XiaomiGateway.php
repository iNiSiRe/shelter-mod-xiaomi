<?php

namespace Shelter\Module\Xiaomi;

class XiaomiGateway extends Device
{
    /**
     * @param array<string,SubDevice> $subDevices
     */
    public function __construct(
        string $did,
        string $host,
        string $token,
        readonly array $subDevices = []
    )
    {
        parent::__construct($did, $host, $token, []);
    }
}