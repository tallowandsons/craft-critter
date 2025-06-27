<?php

namespace mijewe\craftcriticalcssgenerator\storage;

use craft\base\Model;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\StorageResponse;

class BaseStorage extends Model implements StorageInterface
{
    public function get(mixed $key): StorageResponse
    {
        return new StorageResponse();
    }

    public function save(mixed $key, CssModel $css): bool
    {
        return false;
    }
}
