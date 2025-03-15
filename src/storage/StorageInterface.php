<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\StorageResponse;

interface StorageInterface
{
    public function get(string $key): StorageResponse;

    public function save(string $key, CssModel $css): bool;
}
