<?php

namespace honchoagency\craftcriticalcssgenerator\storage;

use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\StorageResponse;

interface StorageInterface
{
    public function get(mixed $key): StorageResponse;

    public function save(mixed $key, CssModel $css): bool;
}
