<?php

namespace mijewe\craftcriticalcssgenerator\storage;

use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\StorageResponse;

interface StorageInterface
{
    public function get(mixed $key): StorageResponse;

    public function save(mixed $key, CssModel $css): bool;
}
