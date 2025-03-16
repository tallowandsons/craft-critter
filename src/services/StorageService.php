<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
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
        $css->stamp($key);

        return $this->storage->save($key, $css);
    }

    public function getCacheKey(UrlModel $url): string
    {
        $path = $this->normaliseUriPath($url);
        $key = null;

        if ($path == "") {
            $key = "index";
        }

        return 'critical-css-' . $key;
    }

    public function normaliseUriPath(UrlModel $url)
    {
        return $url->getSafeUrl();
    }
}
