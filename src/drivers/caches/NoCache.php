<?php

namespace tallowandsons\critter\drivers\caches;

use tallowandsons\critter\models\UrlModel;

/**
 * No Cache model - a null object implementation that performs no cache operations
 */
class NoCache extends BaseCache implements CacheInterface
{

    public string $handle = 'nocache';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'No Cache';
    }

    /**
     * @inheritdoc
     *
     * @param UrlModel|UrlModel[] $urlModels
     */
    public function resolveCache(UrlModel|array $urlModels): void
    {
        // Do nothing - this is a no-op cache implementation
    }
}
