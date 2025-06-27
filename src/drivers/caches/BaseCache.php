<?php

namespace mijewe\craftcriticalcssgenerator\drivers\caches;

use craft\base\Model;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

class BaseCache extends Model implements CacheInterface
{

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel|array $urlModels): void {}

    protected function getCacheBehaviour()
    {
        return Critical::getInstance()->settings->cacheBehaviour;
    }
}
