<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
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

    public function get(UrlModel $url): ?string
    {
        $key = $this->getCacheKey($url);
        return $this->storage->get($key);
    }

    public function save(UrlModel $url, string $css): bool
    {
        $key = $this->getCacheKey($url);

        $formattedCss = $this->formatCss($key, $css);

        return $this->storage->save($key, $formattedCss);
    }

    public function getCacheKey(UrlModel $url): string
    {
        return 'critical-css-' . $this->normaliseUriPath($url);
    }

    public function normaliseUriPath(UrlModel $url)
    {
        return $url->getSafeUrl();
    }

    public function formatCss(string $key, string $css): string
    {
        $datetime = new \DateTime();

        $header = "/* Critical CSS - $key */";
        $footer = "/* generated at " . $datetime->format('Y-m-d H:i:s') . " */";

        return $header . PHP_EOL . $css . PHP_EOL . $footer;
    }
}
