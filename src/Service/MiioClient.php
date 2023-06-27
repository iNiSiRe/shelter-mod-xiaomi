<?php


namespace Shelter\Module\Xiaomi\Service;


use Psr\Log\LoggerInterface;
use React\Datagram\Factory;
use React\Datagram\Socket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Shelter\Module\Xiaomi\Device;

class MiioClient
{
    private const HANDSHAKE_TTL = 60 * 60;

    private LoggerInterface $logger;
    private LoopInterface $loop;
    private Factory $datagramFactory;

    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->loop = $loop;
        $this->datagramFactory = new Factory($loop);
    }

    public static function encrypt(string $data, string $secret)
    {
        $key = md5(hex2bin($secret));
        $iv = md5(hex2bin($key . $secret));

        return openssl_encrypt($data, 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv));
    }

    public static function decrypt(string $data, string $secret)
    {
        $key = md5(hex2bin($secret));
        $iv = md5(hex2bin($key . $secret));

        return openssl_decrypt($data, 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv));
    }

    public static function createHelloPacket()
    {
        return hex2bin('21310020ffffffffffffffffffffffffffffffffffffffffffffffffffffffff');
    }

    private function packPacket(Device $device, string $message)
    {
        $message = bin2hex($this->encrypt($message, $device->token));

//        $structure = [
//            'magic' => '2131',
//            'length' => strlen($message) / 2 + 32,
//            'null' => 0,
//            'deviceType' => 2060,
//            'deviceId' => 45549,
//            'timestamp' => time() - $device->timestampDelta,
//            'checksum' => 'b43200845dc5ad48740d7a1aaafd4445',
//            'data' => $message
//        ];

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

    public static function unpackPacket(string $data): array
    {
        return unpack('H4Magic/nLength/LNull/nDeviceType/nDeviceId/NTimestamp/H32Checksum/H*Data', $data);
    }

    private function sendData(Socket $socket, string $data): PromiseInterface
    {
        $deferred = new Deferred();

        $socket->send($data);

        $timer = $this->loop->addTimer(5, function () use ($socket, $deferred) {
            $socket->close();
            $deferred->reject();
        });

        $socket->on('message', function ($message) use ($deferred, $timer) {
            $this->loop->cancelTimer($timer);
            $deferred->resolve($message);
        });

        $socket->on('error', function ($error) use ($deferred) {
            $deferred->reject($error);
        });

        return $deferred->promise();
    }

    public function hello(Device $device)
    {
        $this->logger->debug('Call "Hello" to device', ['channel' => __CLASS__, 'device' => $device->did]);

        return $this
            ->call($device, 'miIO.info')
            ->then(function ($response) use ($device) {
                $device->runtime['info'] = $response['result'];
                $this->logger->debug('Info completed', ['channel' => __CLASS__, 'response' => json_encode($response)]);
            });
    }

    public function call(Device $device, string $method, array $params = [])
    {
        $this->logger->debug(sprintf('Call "%s"', $method), ['channel' => __CLASS__, 'device' => $device->did]);

        return $this->datagramFactory
            ->createClient($device->host . ':54321')
            ->then(function (Socket $socket) use ($device) {
                if (time() - $device->handshakeAt > self::HANDSHAKE_TTL) {
                    return $this
                        ->sendData($socket, self::createHelloPacket())
                        ->then(function ($response) use ($device, $socket) {
                            $packet = $this->unpackPacket($response);

                            $device->runtime['packetId'] = 1;
                            $device->runtime['deviceId'] = $packet['DeviceId'];
                            $device->runtime['deviceType'] = $packet['DeviceType'];
                            $device->runtime['timestampDelta'] = $packet['Timestamp'] - time();
                            $device->handshakeAt = time();

                            $this->logger->debug('Handshake completed', ['channel' => __CLASS__, 'device' => $device->did, 'packet' => json_encode($packet)]);

                            return $socket;
                        });
                } else {
                    return $socket;
                }
            })
            ->then(function (Socket $socket) use ($device, $method, $params) {
//                $packetId = &$device->runtime['packetId'];
//
//                $packetId++;
//
//                if ($packetId > 9999) {
//                    $packetId = 1;
//                }

                $message = json_encode([
                    'id' => random_int(100000000, 999999999),
                    'method' => $method,
                    'params' => $params
                ]);

                $this->logger->debug('Call request', ['channel' => __CLASS__, 'message' => $message]);

                $data = $this->packPacket($device, $message);

                return $this
                    ->sendData($socket, $data)
                    ->then(function ($response) use ($socket, $device) {
                        // Release resources
                        $socket->close();

                        $packet = $this->unpackPacket($response);
                        $response = $this->decrypt(hex2bin($packet['Data']), $device->token);

                        $this->logger->debug('Call response', ['channel' => __CLASS__, 'device' => $device->did, 'response' => $response]);

                        return json_decode($response, true);
                    });
            });
    }
}