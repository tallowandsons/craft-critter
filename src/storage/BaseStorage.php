<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

class BaseStorage implements StorageInterface
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
