<?php

namespace mijewe\critter\services;

use Craft;
use craft\elements\Entry;
use mijewe\critter\Critter;
use mijewe\critter\drivers\caches\CacheInterface;
use mijewe\critter\drivers\caches\NoCache;
use mijewe\critter\factories\UrlFactory;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\Settings;
use mijewe\critter\models\UrlModel;
use yii\base\Component;

/**
 * Cache Service service
 */
class CacheService extends Component
{
    public CacheInterface $cache;

    public function __construct()
    {
        $cacheClass = Critter::getInstance()->settings->cacheType;

        // Fallback to NoCache if the specified cache class doesn't exist
        if (!$cacheClass || !class_exists($cacheClass)) {
            $cacheClass = NoCache::class;
        }

        $this->cache = new $cacheClass();
    }

    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(CssRequest $cssRequest): void
    {
        $url = $cssRequest->getUrl();
        $mode = $cssRequest->getMode();
        $cacheType = get_class($this->cache);

        Critter::getInstance()->log->debug("Resolving cache for '{$url->getAbsoluteUrl()}' in {$mode} mode using {$cacheType}", 'cache');

        switch ($mode) {
            case Settings::MODE_ENTRY:
                $this->resolveCacheForUrl($url);
                break;
            case Settings::MODE_SECTION:
                $this->resolveCacheForSection($url);
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
        $cacheType = get_class($this->cache);
        Critter::getInstance()->log->logCacheOperation('resolve-entry', $url->getAbsoluteUrl(), $cacheType);
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
            Critter::getInstance()->log->warning("Could not resolve cache for section; url does not have a section: {$url->getAbsoluteUrl()}", 'cache');
            return;
        }

        // get all urls for the section
        $entries = Entry::find()->section($sectionHandle)->all();
        $cacheType = get_class($this->cache);

        Critter::getInstance()->log->logCacheOperation("resolve-section ({$sectionHandle}, " . count($entries) . " entries)", $url->getAbsoluteUrl(), $cacheType);

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
