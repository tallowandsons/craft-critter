<?php

namespace mijewe\critter\drivers\caches;

use mijewe\critter\models\Settings;
use mijewe\critter\models\UrlModel;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\SiteUriModel;

/**
 * Blitz Cache model
 */
class BlitzCache extends BaseCache implements CacheInterface
{

    /**
     * @inheritdoc
     *
     * @param UrlModel|UrlModel[] $urlModels
     */
    public function resolveCache(UrlModel|array $urlModels): void
    {
        $cacheBehaviour = $this->getCacheBehaviour();

        // if $urlModels is a single UrlModel, convert it to an array
        if (!is_array($urlModels)) {
            $urlModels = [$urlModels];
        }

        switch ($cacheBehaviour) {
            case Settings::CACHE_BEHAVIOUR_EXPIRE_URL:
                $this->expireUrls($urlModels);
                break;
            case Settings::CACHE_BEHAVIOUR_CLEAR_URL:
                $this->clearUrls($urlModels);
                break;
            case Settings::CACHE_BEHAVIOUR_REFRESH_URL:
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
