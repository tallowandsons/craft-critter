<?php

namespace mijewe\craftcriticalcssgenerator\storage;

use Craft;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\StorageResponse;

class CraftCacheStorage extends BaseStorage
{
    public function get(mixed $key): StorageResponse
    {
        /** @var CssModel $css */
        $css = Craft::$app->getCache()->get($key);

        $response = new StorageResponse();
        $response->setSuccess($css !== false);
        $response->setData($css);

        return $response;
    }

    public function save(mixed $key, CssModel $cssModel): bool
    {
        return Craft::$app->getCache()->set($key, $cssModel);
    }
}
