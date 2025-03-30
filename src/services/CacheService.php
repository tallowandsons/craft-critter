<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use craft\elements\Entry;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\drivers\caches\CacheInterface;
use honchoagency\craftcriticalcssgenerator\factories\UrlFactory;
use honchoagency\craftcriticalcssgenerator\models\CssRequest;
use honchoagency\craftcriticalcssgenerator\models\Settings;
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

    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(CssRequest $cssRequest): void
    {
        $url = $cssRequest->getUrl();
        $mode = $cssRequest->getMode();

        if ($mode == Settings::MODE_URL) {
            $this->cache->resolveCache($url);
        } else if ($mode == Settings::MODE_ENTRY_TYPE) {

            // get all urls for the entry type
            $entries = Entry::find()
                ->type($url->getEntryType())
                ->all();

            $urls = [];
            foreach ($entries as $entry) {
                $urls[] = UrlFactory::createFromEntry($entry);
            }

            $this->cache->resolveCache($urls);
        }
    }
}
