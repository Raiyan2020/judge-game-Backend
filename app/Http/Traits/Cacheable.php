<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait Cacheable
{
    protected function cache(
        string $key,
        callable $callback,
        ?string $tag = null,
        ?int $ttl = null
    ) {
        $ttlSeconds =  (int) ($ttl ?? config('cache.ttl.default'));

        $expiresAt = Carbon::now()->addSeconds($ttlSeconds);

        if ($tag) {
            return Cache::tags($tag)->remember($key, $expiresAt, $callback);
        }

        return Cache::remember($key, $expiresAt, $callback);
    }
}
