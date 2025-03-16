<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\caches;

use honchoagency\craftcriticalcssgenerator\models\UrlModel;

interface CacheInterface
{
    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(UrlModel $url): void;
}
