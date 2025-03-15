<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use craft\base\Model;

class BaseStorage extends Model implements StorageInterface
{
    public function get(string $key): ?string
    {
        return null;
    }

    public function save(string $key, string $css): bool
    {
        return false;
    }
}
