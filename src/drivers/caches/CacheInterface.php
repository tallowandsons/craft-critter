<?php

namespace tallowandsons\critter\drivers\caches;

use tallowandsons\critter\models\UrlModel;

interface CacheInterface
{
    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(UrlModel|array $urlModels): void;

    public function getUnavailableWarning(): ?string;
}
