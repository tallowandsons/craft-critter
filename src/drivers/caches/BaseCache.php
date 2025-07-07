<?php

namespace mijewe\critter\drivers\caches;

use craft\base\Model;
use mijewe\critter\Critter;
use mijewe\critter\models\UrlModel;

class BaseCache extends Model implements CacheInterface
{

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel|array $urlModels): void {}

    protected function getCacheBehaviour()
    {
        return Critter::getInstance()->settings->cacheBehaviour;
    }
}
