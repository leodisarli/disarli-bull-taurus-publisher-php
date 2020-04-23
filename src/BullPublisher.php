<?php

namespace BullPublisher;

use Ramsey\Uuid\Uuid;
use Predis\Client;

class BullPublisher
{
    public $redisHost;
    public $redisPort;
    private $prefix = 'bull';
    private $token;
    private $keyPrefix;

    /**
     * constructor
     * @param string $redisHost
     * @param int $redisPort
     */
    public function __construct(
        string $redisHost = 'localhost',
        int $redisPort = 6379
    ) {
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
    }

    public function add($queue, $name, $data = [], $opts = [])
    {
        $uuid = $this->newUuid();
        $this->token = $uuid->toString();

        $this->keyPrefix = sprintf('%s:%s:', $this->prefix, $queue);
        
        if (!is_string($name)) {
            $opts = $data;
            $data = $name;
            $name = '__default__';
        }
        $opts = (is_array($opts) ? $opts : [$opts]);

        $timestamp = intval(str_replace('.', '', microtime(true)));
        $delay = (isset($opts['delay']) ? intval($opts['delay']) : 0);

        $defaults = [
            'attempts'  => 1,
            'timestamp' => $timestamp,
            'delay'     => $delay,
        ];
        $options = array_merge_recursive($defaults, $opts);
        $redis = $this->newClient();
        $redis->getProfile()->defineCommand('addjob', 'BullPublisher\RedisAddCommand');
        return $redis->addjob(
            $this->keyPrefix . 'wait',
            $this->keyPrefix . 'paused',
            $this->keyPrefix . 'meta-paused',
            $this->keyPrefix . 'id',
            $this->keyPrefix . 'delayed',
            $this->keyPrefix . 'priority',
            $this->keyPrefix,
            (isset($opts['customJobId']) ? $opts['customJobId'] : ''),
            $name,
            json_encode($data),
            json_encode($options),
            $timestamp,
            $delay,
            ($delay ? $timestamp + $delay : 0),
            (isset($opts['priority']) ? intval($opts['priority']) : 0),
            (isset($opts['lifo']) ? 'RPUSH' : 'LPUSH'),
            $this->token
        );
    }

    /**
     * @codeCoverageIgnore
     * create uuid instance
     * @return \Ramsey\Uuid\Uuid
     */
    public function newUuid()
    {
        return Uuid::uuid4();
    }

    /**
     * @codeCoverageIgnore
     * create uuid instance
     * @return \Predis\Client
     */
    public function newClient()
    {
        $connection = [
            'host' => $this->redisHost,
            'port' => $this->redisPort
        ];
        return new Client($connection);
    }
}
