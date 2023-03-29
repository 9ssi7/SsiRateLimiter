<?php

namespace Ssi;

require_once "IStorage.php";
require_once "RedisStorage.php";

use Ssi\IStorage;

/**
 * Ssi\RateLimiter - A simple rate limiter
 * 
 * @package Ssi
 * @author Sami Salih İbrahimbaş (@ssibrahimbas) <info@ssibrahimbas.com>
 * @url <https://github.com/ssibrahimbas/SsiRateLimiter>
 * @license  The MIT License (MIT) - <http://opensource.org/licenses/MIT>
 */
class RateLimiter
{
    private int      $maxCapacity;
    private int      $period;
    private IStorage $storage;
    private array    $options = [
        "prefix"       => "rate_limit_",
        "maxCapacity"  => 100,
        "refillPeriod" => 60,
        "useCookie"    => false,
    ];

    public function __construct()
    {
        $this->storage = new RedisStorage();
    }

    /**
     * @param IStorage $storage
     * @return RateLimiter
     */
    public function setStorage(IStorage $storage): RateLimiter
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return IStorage
     */
    public function getStorage(): IStorage
    {
        return $this->storage;
    }

    private function getPrefix(): string
    {
        return $this->options['prefix'];
    }

    /**
     * @param string $prefix
     * @return RateLimiter
     */
    public function setPrefix($prefix): self
    {
        $this->options['prefix'] = $prefix;
        return $this;
    }

    /**
     * @param int $maxCapacity
     * @return RateLimiter
     */
    public function setMaxCapacity($maxCapacity): self
    {
        $this->maxCapacity = $maxCapacity;
        return $this;
    }

    /**
     * @param int $period
     * @return RateLimiter
     */
    public function setPeriod($period): self
    {
        $this->period = $period;
        return $this;
    }

    /**
     * @param bool $useCookie
     * @return RateLimiter
     */
    public function useCookie($useCookie = true): self
    {
        $this->options['useCookie'] = $useCookie;
        return $this;
    }

    private function getCookieOrIPKey(): string
    {
        if ($this->options['useCookie']) {
            return $_COOKIE['PHPSESSID'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return bool
     */
    public function checkCookieOrIP(): bool
    {
        $key = $this->getCookieOrIPKey();
        return $this->check($key);
    }

    /**
     * @return bool
     */
    public function checkIP(): bool
    {
        return $this->check($_SERVER['REMOTE_ADDR']);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function check(string $identifier): bool
    {
        $this->storage->checkConnection();
        $key = $this->getPrefix() . $identifier;
        if (!$this->hasBucket($key)) {
            $this->createBucket($key);
        }
        $currentTime    = time();
        $lastCheck      = $this->storage->get($key . 'last_check');
        $tokensToAdd    = ($currentTime - $lastCheck) * ($this->maxCapacity / $this->period);
        $currentAmmount = $this->storage->get($key);
        $bucket         = $currentAmmount + $tokensToAdd;
        $bucket         = $bucket > $this->maxCapacity ? $this->maxCapacity : $bucket;
        $this->storage->set($key . 'last_check', $currentTime, $this->period);

        if ($bucket < 1) {
            return false;
        }

        $this->storage->set($key, $bucket - 1, $this->period);
        return true;
    }

    private function createBucket(string $key)
    {
        $this->storage->set($key . 'last_check', time(), $this->period);
        $this->storage->set($key, $this->maxCapacity - 1, $this->period);
    }

    private function hasBucket(string $key): bool
    {
        return $this->storage->get($key) !== null;
    }

    /**
     * @param string $identifier
     * @return int
     */
    public function get(string $identifier): int
    {
        $key = $this->getPrefix() . $identifier;
        return $this->storage->get($key);
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function del(string $identifier): void
    {
        $key = $this->getPrefix() . $identifier;
        $this->storage->del($key);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function headers(string $identifier): array
    {
        $key       = $this->getPrefix() . $identifier;
        $lastCheck = $this->storage->get($key . 'last_check');
        $headers   = [
            'X-RateLimit-Limit'     => $this->maxCapacity,
            'X-RateLimit-Remaining' => $this->get($identifier),
            'X-RateLimit-Reset'     => $lastCheck + $this->period,
        ];
        return $headers;
    }
}
