<?php


namespace Shelter\Module\Xiaomi\Service;


use Shelter\Module\Xiaomi\Device;

class Miio
{
    public static function encrypt(string $data, string $secret)
    {
        $key = md5(hex2bin($secret));
        $iv = md5(hex2bin($key.$secret));

        return openssl_encrypt($data, 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv));
    }

    public static function decrypt(string $data, string $secret)
    {
        $key = md5(hex2bin($secret));
        $iv = md5(hex2bin($key.$secret));

        return openssl_decrypt($data, 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv));
    }

    public static function buildAck()
    {
        $structure = [
            'magic' => '2131',
            'length' => 32,
            'null' => 0,
            'deviceType' => 2060,
            'deviceId' => 45549 + 1,
            'timestamp' => 68541,
            'checksum' => 'ffffffffffffffffffffffffffffffff',
            'data' => ''
        ];

        return pack('H4nLnnNH32H*', ... array_values($structure));
    }

    public static function createHelloPacket(string $checksum = 'ffffffffffffffffffffffffffffffff')
    {
        return hex2bin('21310020ffffffffffffffffffffffff' . $checksum);
    }

    public static function packPacket(Device $device, string $message)
    {
        $message = bin2hex(self::encrypt($message, $device->token));

        $structure = [
            'magic' => '2131',
            'length' => strlen($message) / 2 + 32,
            'null' => 0,
            'deviceType' => $device->runtime['deviceType'],
            'deviceId' => $device->runtime['deviceId'],
            'timestamp' => time() + $device->runtime['timestampDelta'],
            'checksum' => $device->token,
            'data' => $message
        ];

        $packet = pack('H4nLnnNH32H*', ... array_values($structure));

        $checksum = md5($packet);
        $structure['checksum'] = $checksum;

        return pack('H4nLnnNH32H*', ... array_values($structure));
    }

    public static function unpackPacket(string $data)
    {
        return unpack('H4Magic/nLength/LNull/nDeviceType/nDeviceId/NTimestamp/H32Checksum/H*Data', $data);
    }
}