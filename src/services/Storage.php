<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\storage\StorageInterface;
use yii\base\Component;

/**
 * Storage service
 */
class Storage extends Component
{

    public StorageInterface $storage;

    public function __construct()
    {
        $storageClass = Critical::getInstance()->settings->storageType;
        $this->storage = new $storageClass();
    }

    public function get(string $path): ?string
    {
        $key = $this->getCacheKey($path);
        return $this->storage->get($key);
    }

    public function save(string $path, string $css): bool
    {
        $key = $this->getCacheKey($path);

        $formattedCss = $this->formatCss($key, $css);

        return $this->storage->save($key, $formattedCss);
    }

    public function getCacheKey(string $uriPath)
    {
        return 'critical-css-' . $this->normaliseUriPath($uriPath);
    }

    public function normaliseUriPath(string $uriPath)
    {
        $path = str_replace('/', '-', $uriPath);
        return $path;
    }

    public function formatCss(string $key, string $css): string
    {
        $datetime = new \DateTime();

        $header = "/* Critical CSS - $key */";
        $footer = "/* generated at " . $datetime->format('Y-m-d H:i:s') . " */";

        return $header . PHP_EOL . $css . PHP_EOL . $footer;
    }
}
