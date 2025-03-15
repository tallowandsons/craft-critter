<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

interface StorageInterface
{
    public function get(string $key): ?string;

    public function save(string $key, string $css): bool;
}
