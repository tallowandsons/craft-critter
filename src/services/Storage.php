<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use yii\base\Component;

/**
 * Storage service
 */
class Storage extends Component
{
    public function get(string $path)
    {
        $key = $this->getCacheKey($path);
        return Craft::$app->getCache()->get($key);
    }

    public function set(string $path, string $css): bool
    {
        $key = $this->getCacheKey($path);
        return Craft::$app->getCache()->set($key, $css);
    }

    public function getCacheKey(string $uriPath)
    {
        return 'critical-css-' . $this->normaliseUriPath($uriPath);
    }

    public function normaliseUriPath(string $uriPath)
    {
        $path = str_replace('/', '-', $uriPath);
        return $path;
    }
}
