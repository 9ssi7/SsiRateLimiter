<?php

namespace Ssi;

use Ssi\IStorage;

class RedisStorage implements IStorage
{
    private $client;
    private $host = 'localhost';
    private $port = 6379;

    public function __construct()
    {
        $this->client = new \Redis();
    }

    public function setClient(\Redis $redis): RedisStorage
    {
        $this->client = $redis;
        return $this;
    }

    public function setHost(string $host): RedisStorage
    {
        $this->host = $host;
        return $this;
    }

    public function setPort(int $port): RedisStorage
    {
        $this->port = $port;
        return $this;
    }

    public function checkConnection()
    {
        if (!$this->client->isConnected()) {
            $this->client->connect($this->host, $this->port);
        }
    }

    public function get(string $key)
    {
        return $this->client->get($key);
    }

    public function set(string $key, $value, int $ttl = 0)
    {
        if ($ttl > 0)
            return $this->client->setex($key, $ttl, $value);
        else
            return $this->client->set($key, $value);
    }

    public function del(string $key)
    {
        return $this->client->del($key);
    }
}
