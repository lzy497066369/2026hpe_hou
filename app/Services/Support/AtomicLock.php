<?php

namespace App\Services\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class AtomicLock
{
    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function run(string $name, Closure $callback, int $seconds = 10): mixed
    {
        $lock = Cache::lock($name, $seconds);

        return $lock->block($seconds, function () use ($callback): mixed {
            return $callback();
        });
    }
}
