<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use craft\helpers\ArrayHelper;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use honchoagency\craftcriticalcssgenerator\records\UriRecord;
use honchoagency\craftcriticalcssgenerator\storage\StorageInterface;
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

    public function get(UrlModel $url): CssModel
    {
        $key = $this->getCacheKey($url);

        /* @var StorageResponse $response */
        $response = $this->storage->get($key);

        if (!$response->isSuccess()) {
            return new CssModel();
        }

        return $response->getData();
    }

    public function save(UrlModel $url, CssModel $css): bool
    {
        $key = $this->getCacheKey($url);

        // Stamp the CSS with the current datetime and key
        $css->stamp($url->getAbsoluteUrl());

        // save the CSS to the storage
        return $this->storage->save($key, $css);
    }

    public function getCacheKey(UrlModel $url): mixed
    {
        return ArrayHelper::merge(['critical-css'], $url->toArray());
    }

    public function normaliseUriPath(UrlModel $url)
    {
        return $url->getRelativeUrl();
    }
}
