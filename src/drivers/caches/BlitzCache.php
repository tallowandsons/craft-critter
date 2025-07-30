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

        // log
        $urlsString = implode(', ', $urlPatterns);
        Critter::getInstance()->log->logCacheOperation('resolve-cache', "Resolving cache for URLs: {$urlsString}", get_class($this));

        $siteUris = SiteUriHelper::getSiteUrisFromUrls($urlPatterns);

        switch ($this->cacheBehaviour) {
            case self::CACHE_BEHAVIOUR_CLEAR_URLS:
                Blitz::getInstance()->clearCache->clearUris($siteUris);
                break;
            case self::CACHE_BEHAVIOUR_EXPIRE_URLS:
                Blitz::getInstance()->expireCache->expireUris($siteUris);
                break;
            case self::CACHE_BEHAVIOUR_REFRESH_URLS:
                Blitz::getInstance()->refreshCache->refreshSiteUris($siteUris);
                break;
        }
    }
}
