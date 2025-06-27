<?php

namespace mijewe\craftcriticalcssgenerator\services;

use Craft;
use craft\elements\Entry;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\drivers\caches\CacheInterface;
use mijewe\craftcriticalcssgenerator\factories\UrlFactory;
use mijewe\craftcriticalcssgenerator\models\CssRequest;
use mijewe\craftcriticalcssgenerator\models\Settings;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
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

        switch ($mode) {
            case Settings::MODE_URL:
                $this->resolveCacheForUrl($url);
                break;
            case Settings::MODE_SECTION:
                $this->resolveCacheForSection($url);
                break;
            case Settings::MODE_ENTRY_TYPE:
                $this->resolveCacheForEntryType($url);
                break;
            default:
                throw new \Exception("Could not resolve cache; invalid mode: $mode");
        }
    }

    /**
     * resolve the cache for a single url
     */
    private function resolveCacheForUrl(UrlModel $url): void
    {
        $this->cache->resolveCache([$url]);
    }

    /**
     * resolve the cache for all urls in the section
     * of the given url
     */
    private function resolveCacheForSection(UrlModel $url): void
    {
        $sectionHandle = $url->getSectionHandle();
        if (!$sectionHandle) {
            Craft::warning("Could not resolve cache for section; url does not have a section", __METHOD__);
            return;
        }

        // get all urls for the section
        $entries = Entry::find()->section($sectionHandle)->all();

        $this->resolveCacheForEntries($entries);
    }

    /**
     * resolve the cache for all urls with the entry type
     * of the given url
     */
    private function resolveCacheForEntryType(UrlModel $url): void
    {
        $entryType = $url->getEntryTypeHandle();
        if (!$entryType) {
            Craft::warning("Could not resolve cache for entry type; url does not have an entry type", __METHOD__);
            return;
        }

        // get all urls for the entry type
        $entries = Entry::find()->type($entryType)->all();

        $this->resolveCacheForEntries($entries);
    }

    /**
     * resolve the cache for an array of entries
     */
    private function resolveCacheForEntries(array $entries): void
    {
        $urls = array_map(fn($entry) => UrlFactory::createFromEntry($entry), $entries);
        $this->cache->resolveCache($urls);
    }
}
