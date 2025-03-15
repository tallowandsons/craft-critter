<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper;

class FileStorage extends BaseStorage
{
    public string $folderPath = '@runtime/criticalcss';
    private ?string $cacheFolderPath;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!empty($this->folderPath)) {
            $this->cacheFolderPath = FileHelper::normalizePath(
                App::parseEnv($this->folderPath)
            );
        }
    }

    public function get(string $key): ?string
    {
        return Craft::$app->getCache()->get($key);
    }

    public function save(string $key, string $css): bool
    {
        return Craft::$app->getCache()->set($key, $css);
    }
}
