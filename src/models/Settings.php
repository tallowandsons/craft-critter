<?php

namespace mijewe\craftcriticalcssgenerator\models;

use craft\base\Model;
use mijewe\craftcriticalcssgenerator\generators\DummyGenerator;
use mijewe\craftcriticalcssgenerator\storage\CraftCacheStorage;

/**
 * Critical CSS Generator settings
 */
class Settings extends Model
{

    const MODE_URL = 'url';
    const MODE_SECTION = 'section';
    const MODE_ENTRY_TYPE = 'entryType';

    const CACHE_BEHAVIOUR_EXPIRE_URL = 'expireUrl';
    const CACHE_BEHAVIOUR_CLEAR_URL = 'clearUrl';
    const CACHE_BEHAVIOUR_REFRESH_URL = 'refreshUrl';

    // whether or not to automatically render the critical css
    public bool $autoRenderEnabled = true;

    // what attributes to pass to the style tag where the critical css is inserted
    public array $styleTagAttributes = [];

    // which generator type to use
    public string $generatorType = DummyGenerator::class;

    // which storage type to use
    public string $storageType = CraftCacheStorage::class;

    // which cache type to use
    public ?string $cacheType = null;

    // what the cache behaviour should be
    public ?string $cacheBehaviour = null;

    // which query string parameters are to be treated as unique urls
    public array $allowedQueryStringParams = [];

    public ?string $generatorApiKey = null;

    public ?string $baseUrlOverride = null;

    // what the default mode should be.
    // this will detemine whether critical css
    // is generated for each url or each entry type
    public string $defaultMode = self::MODE_URL;

    // the settings for each section
    public array $sectionSettings = [];
}
