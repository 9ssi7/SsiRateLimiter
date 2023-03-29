# Ssibrahimbas \ Rate Limiter

a fast, flexible, reliable rate limiter for php

## Installation

```bash
composer require ssibrahimbas/rate-limiter
```

## Usage

```php
<?php

use Ssibrahimbas\RateLimiter;

$rateLimiter = new RateLimiter();

 $isValid     = $rateLimiter->setMaxCapacity(20)
                    ->setPeriod(60)
                    ->useCookie()
                    ->checkCookieOrIP();

// check if the rate limit has been exceeded
if (!$isValid) {
    // ok, you can continue
} else {
    // no, too many requests
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
