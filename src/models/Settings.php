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

    const CACHE_BEHAVIOUR_EXPIRE_URL = 'expireUrl';
    const CACHE_BEHAVIOUR_CLEAR_URL = 'clearUrl';
    const CACHE_BEHAVIOUR_REFRESH_URL = 'refreshUrl';

    // whether or not to automatically render the critical css
    public bool $autoRenderEnabled = true;

    // what options to pass to the style tag where the critical css is inserted
    public array $styleTagOptions = [];

    // which generator type to use
    public string $generatorType = DummyGenerator::class;

    // which storage type to use
    public string $storageType = CraftCacheStorage::class;

    // which cache type to use
    public ?string $cacheType = null;

    // what the cache behaviour should be
    public ?string $cacheBehaviour = null;
}
