<?php

namespace mijewe\critter\storage;

use Craft;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\StorageResponse;
use yii\caching\TagDependency;

class CraftCacheStorage extends BaseStorage
{
    private const CACHE_KEY_PREFIX = 'critter:css:';

    /**
     * Get the main cache component with Critter-specific key prefixing
     */
    private function getCache()
    {
        return Craft::$app->getCache();
    }

    /**
     * Build a cache key with Critter prefix
     */
    private function buildKey(mixed $key): string
    {
        return self::CACHE_KEY_PREFIX . $key;
    }

    public function get(mixed $key): StorageResponse
    {
        /** @var CssModel $css */
        $css = $this->getCache()->get($this->buildKey($key));

        if ($css instanceof CssModel) {
            return (new StorageResponse())
                ->setSuccess(true)
                ->setData($css);
        } else {
            // Not a valid CssModel (could be incomplete class, wrong format, etc.)
            return (new StorageResponse())
                ->setSuccess(false)
                ->setData(new CssModel());
        }
    }

    public function save(mixed $key, CssModel $cssModel): bool
    {
        return $this->getCache()->set(
            $this->buildKey($key),
            $cssModel,
            null, // duration (null = default)
            new TagDependency(['tags' => ['critter-css']])
        );
    }

    public function delete(mixed $key): bool
    {
        return $this->getCache()->delete($this->buildKey($key));
    }

    /**
     * Clear all Critter cache data using cache tags
     */
    public function clearAll(): bool
    {
        TagDependency::invalidate($this->getCache(), 'critter-css');
        return true;
    }
}
