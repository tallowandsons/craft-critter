<?php

namespace tallowandsons\critter\storage;

use craft\base\Model;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\StorageResponse;

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

    public function delete(mixed $key): bool
    {
        return false;
    }

    public function clearAll(): bool
    {
        return false;
    }
}
