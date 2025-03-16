<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\drivers\caches\CacheInterface;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use yii\base\Component;

/**
 * Cache Service service
 */
class CacheService extends Component
{
    public CacheInterface $cache;

    public function __construct()
    {
        $cacheClass = Critical::getInstance()->settings->cacheType;
        $this->cache = new $cacheClass();
    }

    public function expireUrl(UrlModel $url): void
    {
        $this->cache->expireUrl($url);
    }
}
