<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\caches;

use Craft;
use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\SiteUriModel;

/**
 * Blitz Cache model
 */
class BlitzCache extends BaseCache implements CacheInterface
{
    public function expireUrl(UrlModel $urlModel): void
    {
        $blitzSiteUriModel = $this->convertUrlToBlitzSiteUriModel($urlModel);
        Blitz::getInstance()->expireCache->expireUris([$blitzSiteUriModel]);
    }

    private function convertUrlToBlitzSiteUriModel(UrlModel $urlModel)
    {
        return new SiteUriModel([
            'siteId' => $urlModel->siteId,
            'uri' => $urlModel->url,
        ]);
    }
}
