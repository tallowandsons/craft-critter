<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\generators\DummyGenerator;
use honchoagency\craftcriticalcssgenerator\storage\CraftCacheStorage;

/**
 * Critical CSS Generator settings
 */
class Settings extends Model
{
    public string $generatorType = DummyGenerator::class;
    public string $storageType = CraftCacheStorage::class;
    public array $styleTagOptions = [];
}
