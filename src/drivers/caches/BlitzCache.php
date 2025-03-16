<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\caches;

use honchoagency\craftcriticalcssgenerator\models\Settings;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\SiteUriModel;

/**
 * Blitz Cache model
 */
class BlitzCache extends BaseCache implements CacheInterface
{

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel $urlModel): void
    {
        $cacheBehaviour = $this->getCacheBehaviour();

        switch ($cacheBehaviour) {
            case Settings::CACHE_BEHAVIOUR_EXPIRE_URL:
                $this->expireUrl($urlModel);
                break;
            case Settings::CACHE_BEHAVIOUR_CLEAR_URL:
                $this->clearUrl($urlModel);
                break;
            case Settings::CACHE_BEHAVIOUR_REFRESH_URL:
                $this->refreshUrl($urlModel);
                break;
        }
    }

    public function expireUrl(UrlModel $urlModel): void
    {
        $blitzSiteUriModel = $this->convertUrlToBlitzSiteUriModel($urlModel);
        Blitz::getInstance()->expireCache->expireUris([$blitzSiteUriModel]);
    }

    public function clearUrl(UrlModel $urlModel): void
    {
        $blitzSiteUriModel = $this->convertUrlToBlitzSiteUriModel($urlModel);
        Blitz::getInstance()->clearCache->clearUris([$blitzSiteUriModel]);
    }

    public function refreshUrl(UrlModel $urlModel): void
    {
        $blitzSiteUriModel = $this->convertUrlToBlitzSiteUriModel($urlModel);
        Blitz::getInstance()->refreshCache->refreshSiteUris([$blitzSiteUriModel]);
    }

    private function convertUrlToBlitzSiteUriModel(UrlModel $urlModel)
    {
        return new SiteUriModel([
            'siteId' => $urlModel->siteId,
            'uri' => $urlModel->url,
        ]);
    }
}
