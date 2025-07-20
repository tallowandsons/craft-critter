<?php

namespace mijewe\critter\storage;

use Craft;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\StorageResponse;

class CraftCacheStorage extends BaseStorage
{
    public function get(mixed $key): StorageResponse
    {
        /** @var CssModel $css */
        $css = Craft::$app->getCache()->get($key);

        if ($css instanceof CssModel) {
            return (new StorageResponse())
                ->setSuccess(true)
                ->setData($css);
        } else {
            // Not a valid CssModel (could be incomplete class, wrong format, etc.)
            return (new StorageResponse())
                ->setSuccess(false)
                ->setData(new CssModel());
        }
    }

    public function save(mixed $key, CssModel $cssModel): bool
    {
        return Craft::$app->getCache()->set($key, $cssModel);
    }

    public function delete(mixed $key): bool
    {
        return Craft::$app->getCache()->delete($key);
    }
}
