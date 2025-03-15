<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\StorageResponse;

class BaseStorage extends Model implements StorageInterface
{
    public function get(string $key): StorageResponse
    {
        return new StorageResponse();
    }

    public function save(string $key, CssModel $css): bool
    {
        return false;
    }
}
