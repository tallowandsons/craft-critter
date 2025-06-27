<?php

namespace mijewe\craftcriticalcssgenerator\services;

use craft\helpers\ArrayHelper;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\CssRequest;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
use mijewe\craftcriticalcssgenerator\storage\StorageInterface;
use yii\base\Component;

/**
 * Storage service
 */
class StorageService extends Component
{

    public StorageInterface $storage;

    public function __construct()
    {
        $storageClass = Critical::getInstance()->settings->storageType;
        $this->storage = new $storageClass();
    }

    public function get(CssRequest $cssRequest): CssModel
    {
        $key = $this->getCacheKey($cssRequest->getKey());

        $response = $this->storage->get($key);

        // if the response is not successful,
        // return an empty CssModel
        if (!$response->isSuccess()) {
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
