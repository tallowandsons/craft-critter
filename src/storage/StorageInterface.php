<?php

namespace mijewe\critter\storage;

use mijewe\critter\models\CssModel;
use mijewe\critter\models\StorageResponse;

interface StorageInterface
{
    public function get(mixed $key): StorageResponse;

    public function save(mixed $key, CssModel $css): bool;
}
