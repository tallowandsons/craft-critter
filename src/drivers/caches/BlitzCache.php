<?php

namespace tallowandsons\critter\drivers\caches;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\UrlModel;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\helpers\SiteUriHelper;

/**
 * Blitz Cache model
 */
class BlitzCache extends BaseCache implements CacheInterface
{

    public string $handle = 'blitz';

    const CACHE_BEHAVIOUR_EXPIRE_URLS = 'expireUrls';
    const CACHE_BEHAVIOUR_CLEAR_URLS = 'clearUrls';
    const CACHE_BEHAVIOUR_REFRESH_URLS = 'refreshUrls';

    // the cache behaviour to use when resolving cache
    public string $cacheBehaviour = self::CACHE_BEHAVIOUR_REFRESH_URLS;

    public function __construct()
    {
        $cacheSettings = Critter::getInstance()->settings->cacheSettings ?? [];
        $this->cacheBehaviour = $cacheSettings['cacheBehaviour'] ?? $this->cacheBehaviour;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Blitz';
    }

    /**
     * Returns an array of all available cache behaviours as <select> options.
     */
    public static function getCacheBehaviourOptions(): array
    {
        return [
            [
                'label' => Critter::translate('Clear URLs'),
                'value' => self::CACHE_BEHAVIOUR_CLEAR_URLS,
            ],
            [
                'label' => Critter::translate('Expire URLs'),
                'value' => self::CACHE_BEHAVIOUR_EXPIRE_URLS,
            ],
            [
                'label' => Critter::translate('Refresh URLs'),
                'value' => self::CACHE_BEHAVIOUR_REFRESH_URLS,
            ],
        ];
    }

    /**
     * Return the settings for this cache.
     * This is used to display the settings in the CP.
     */
    public function getSettings(): array
    {
        return [
            'cacheBehaviourOptions' => self::getCacheBehaviourOptions(),
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
            'pluginHandle' => Critter::getPluginHandle(),
        ];
    }

    /**
     * @inheritdoc
     *
     * @param UrlModel|UrlModel[] $urlModels
     */
    public function resolveCache(UrlModel|array $urlModels): void
    {
        // Check if Blitz plugin is installed and enabled
        if (!$this->isBlitzPluginAvailable()) {
            Critter::getInstance()->log->logCacheOperation('blitz-unavailable', "Blitz plugin is not installed or enabled. Skipping cache operation.", get_class($this));
            return;
        }

        // if $urlModels is a single UrlModel, convert it to an array
        if (!is_array($urlModels)) {
            $urlModels = [$urlModels];
        }

        $urlPatterns = [];

        foreach ($urlModels as $urlModel) {

            // get URL without query string or base URL override
            // this is to ensure we match the original URL that Blitz cached
            $url = $urlModel->getAbsoluteUrl(false, false);

            $urlPatterns[] = $url; // Exact URL: /about
            $urlPatterns[] = $url . '?*'; // URL with any query string: /about?*
        }

        $urlsString = implode(', ', $urlPatterns);

        $siteUris = SiteUriHelper::getSiteUrisFromUrls($urlPatterns);

        switch ($this->cacheBehaviour) {
            case self::CACHE_BEHAVIOUR_CLEAR_URLS:
                Critter::getInstance()->log->logCacheOperation('clear-cache', "Clearing cache for URLs: {$urlsString}", get_class($this));
                Blitz::getInstance()->clearCache->clearUris($siteUris);
                break;
            case self::CACHE_BEHAVIOUR_EXPIRE_URLS:
                Critter::getInstance()->log->logCacheOperation('expire-cache', "Expiring cache for URLs: {$urlsString}", get_class($this));
                Blitz::getInstance()->expireCache->expireUris($siteUris);
                break;
            case self::CACHE_BEHAVIOUR_REFRESH_URLS:
                Critter::getInstance()->log->logCacheOperation('refresh-cache', "Refreshing cache for URLs: {$urlsString}", get_class($this));
                Blitz::getInstance()->refreshCache->refreshSiteUris($siteUris);
                break;
            default:
                Critter::getInstance()->log->logCacheOperation('unknown-cache-behaviour', "Unknown cache behaviour: {$this->cacheBehaviour}", get_class($this));
                throw new \Exception("Unknown cache behaviour: {$this->cacheBehaviour}");
        }
    }

    /**
     * Check if the Blitz plugin is installed and enabled
     */
    public function isBlitzPluginAvailable(): bool
    {
        try {
            return \Craft::$app && \Craft::$app->plugins && \Craft::$app->plugins->getPlugin('blitz') !== null;
        } catch (\Throwable $e) {
            // Not in Craft context or Blitz not available
            return false;
        }
    }

    /**
     * Get warning message if Blitz plugin is not available
     */
    public function getUnavailableWarning(): ?string
    {
        if (!$this->isBlitzPluginAvailable()) {
            return Critter::translate('The Blitz plugin is not installed or enabled. This cache driver will not function without the Blitz plugin.');
        }
        return null;
    }
}
