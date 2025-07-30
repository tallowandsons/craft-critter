<?php

namespace tallowandsons\critter\storage;

use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\StorageResponse;

interface StorageInterface
{
    public function get(mixed $key): StorageResponse;

    public function save(mixed $key, CssModel $css): bool;

    public function delete(mixed $key): bool;

    public function clearAll(): bool;
}
