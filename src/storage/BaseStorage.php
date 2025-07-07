<?php

namespace mijewe\critter\storage;

use craft\base\Model;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\StorageResponse;

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
