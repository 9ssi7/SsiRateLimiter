<?php

namespace Ssi;

interface IStorage
{
    public function get(string $key);
    public function checkConnection();
    public function setHost(string $host): IStorage;
    public function setPort(int $port): IStorage;
    public function setClient(\Redis $redis): IStorage;
    public function set(string $key, $value, int $ttl = 0);
    public function del(string $key);
}
