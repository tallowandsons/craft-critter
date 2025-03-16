<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\caches;

use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class BaseCache extends Model implements CacheInterface
{

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel $url): void {}

    protected function getCacheBehaviour()
    {
        return Critical::getInstance()->settings->cacheBehaviour;
    }
}
