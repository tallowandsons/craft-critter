<?php

namespace mijewe\critter\drivers\caches;

use mijewe\critter\models\UrlModel;

interface CacheInterface
{
    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(UrlModel|array $urlModels): void;
}
