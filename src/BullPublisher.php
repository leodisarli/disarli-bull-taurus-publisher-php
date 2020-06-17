<?php

namespace BullPublisher;

use Ulid\Ulid;
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
        $ulid = $this->newUlid();
        $this->token = $ulid->generate();

        $this->keyPrefix = sprintf('%s:%s:', $this->prefix, $queue);

        if (empty($name)) {
            $name = 'process';
        }
        $opts = (is_array($opts) ? $opts : [$opts]);

        $attempts = (isset($opts['attempts']) ? intval($opts['attempts']) : 3);
        $backoff = (isset($opts['backoff']) ? intval($opts['backoff']) : 30000);
        $delay = (isset($opts['delay']) ? intval($opts['delay']) : 0);
        $removeOnComplete = (isset($opts['removeOnComplete']) ? intval($opts['removeOnComplete']) : 100);
        $timestamp = intval(str_replace('.', '', microtime(true)));
        $jobId = (isset($opts['jobId']) ? $opts['jobId'] : $ulid->generate());

        $defaults = [
            'attempts' => $attempts,
            'backoff' => $backoff,
            'delay' => $delay,
            'removeOnComplete' => $removeOnComplete,
            'timestamp' => $timestamp,
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
            $jobId,
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
     * @return \Ramsey\Ulid\Ulid
     */
    public function newUlid()
    {
        return new Ulid();
    }

    /**
     * @codeCoverageIgnore
     * create predis client instance
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
