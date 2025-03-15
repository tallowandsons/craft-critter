<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use Craft;

class CraftCacheStorage extends BaseStorage
{
    public function get(string $key): ?string
    {
        return Craft::$app->getCache()->get($key);
    }

    public function save(string $key, string $css): bool
    {
        return Craft::$app->getCache()->set($key, $css);
    }
}
