<?php

namespace mijewe\critter\drivers\caches;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\models\Settings;
use mijewe\critter\models\UrlModel;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\SiteUriModel;

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
    public string $cacheBehaviour;

    public function __construct()
    {
        $cacheSettings = Critter::getInstance()->settings->cacheSettings ?? [];
        $this->cacheBehaviour = $cacheSettings['cacheBehaviour'] ?? self::CACHE_BEHAVIOUR_REFRESH_URLS;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Blitz Cache';
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

        switch ($this->cacheBehaviour) {
            case self::CACHE_BEHAVIOUR_EXPIRE_URLS:
                $this->expireUrls($urlModels);
                break;
            case self::CACHE_BEHAVIOUR_CLEAR_URLS:
                $this->clearUrls($urlModels);
                break;
            case self::CACHE_BEHAVIOUR_REFRESH_URLS:
                $this->refreshUrls($urlModels);
                break;
        }
    }

    private function expireUrls(array $urlModels): void
    {
        $blitzSiteUriModels = $this->convertUrlToBlitzSiteUriModels($urlModels);
        Blitz::getInstance()->expireCache->expireUris($blitzSiteUriModels);
    }

    private function clearUrls(array $urlModels): void
    {
        $blitzSiteUriModels = $this->convertUrlToBlitzSiteUriModels($urlModels);
        Blitz::getInstance()->clearCache->clearUris($blitzSiteUriModels);
    }

    private function refreshUrls(array $urlModels): void
    {
        $blitzSiteUriModels = $this->convertUrlToBlitzSiteUriModels($urlModels);
        Blitz::getInstance()->refreshCache->refreshSiteUris($blitzSiteUriModels);
    }

    private function convertUrlToBlitzSiteUriModels(UrlModel|array $urlModels): array
    {
        if (is_array($urlModels)) {
            return array_map([$this, 'convertUrlToBlitzSiteUriModel'], $urlModels);
        }

        return [$this->convertUrlToBlitzSiteUriModel($urlModels)];
    }

    private function convertUrlToBlitzSiteUriModel(UrlModel $urlModel): SiteUriModel
    {
        return new SiteUriModel([
            'siteId' => $urlModel->siteId,
            'uri' => $urlModel->url,
        ]);
    }
}
