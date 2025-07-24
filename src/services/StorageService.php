<?php

namespace mijewe\critter\services;

use craft\helpers\ArrayHelper;
use mijewe\critter\Critter;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\storage\StorageInterface;
use yii\base\Component;

/**
 * Storage service
 */
class StorageService extends Component
{

    public StorageInterface $storage;

    public function __construct()
    {
        $storageClass = Critter::getInstance()->settings->storageType;
        $this->storage = new $storageClass();
    }

    public function get(CssRequest $cssRequest): CssModel
    {
        $key = $this->getCacheKey($cssRequest->getKey());

        $response = $this->storage->get($key);

        // if the response is not successful,
        // delete the cache entry if it exists and
        // return an empty CssModel
        if (!$response->isSuccess()) {
            $this->storage->delete($key);
            return new CssModel();
        }

        return $response->getData();
    }

    public function save(CssRequest $cssRequest, CssModel $css): bool
    {
        $key = $this->getCacheKey($cssRequest->getKey());
        $url = $cssRequest->getUrl();

        // stamp the CSS with the current datetime and key
        $css->stamp($url->getAbsoluteUrl());

        // save the CSS to the storage
        return $this->storage->save($key, $css);
    }

    public function delete(CssRequest $cssRequest): bool
    {
        $key = $this->getCacheKey($cssRequest->getKey());
        return $this->storage->delete($key);
    }

    public function clearAll(): bool
    {
        return $this->storage->clearAll();
    }

    public function getCacheKey(mixed $key): mixed
    {
        // return ArrayHelper::merge(['critical-css'], $url->toArray());
        return $key;
    }

    public function normaliseUriPath(UrlModel $url)
    {
        return $url->getRelativeUrl();
    }
}
