<?php

namespace mijewe\craftcriticalcssgenerator\models;

use craft\base\Model;
use mijewe\craftcriticalcssgenerator\drivers\caches\BlitzCache;
use mijewe\craftcriticalcssgenerator\generators\CriticalCssDotComGenerator;
use mijewe\craftcriticalcssgenerator\storage\CraftCacheStorage;

/**
 * Critical CSS Generator settings
 */
class Settings extends Model
{

    const MODE_URL = 'url';
    const MODE_SECTION = 'section';

    const CACHE_BEHAVIOUR_EXPIRE_URLS = 'expireUrls';
    const CACHE_BEHAVIOUR_CLEAR_URLS = 'clearUrls';
    const CACHE_BEHAVIOUR_REFRESH_URLS = 'refreshUrls';

    // whether or not to automatically render the critical css
    public bool $autoRenderEnabled = true;

    // what attributes to pass to the style tag where the critical css is inserted
    public array $styleTagAttributes = [];

    // which generator type to use
    public string $generatorType = CriticalCssDotComGenerator::class;

    // the settings for the generator
    public array $generatorSettings = [];

    // which storage type to use
    public string $storageType = CraftCacheStorage::class;

    // which cache type to use
    public ?string $cacheType = BlitzCache::class;

    // what the cache behaviour should be
    public ?string $cacheBehaviour = self::CACHE_BEHAVIOUR_REFRESH_URLS;

    // which query string parameters are to be treated as unique urls
    public array $uniqueQueryParams = [];

    // a base URL override for the critical css generator
    // this is useful if you want to generate critical css for a different domain
    // than the one the site is running on, for example on a staging environment.
    public ?string $baseUrlOverride = null;

    // what the default mode should be.
    // this will determine whether critical css
    // is generated for each url or each section
    public string $defaultMode = self::MODE_SECTION;

    // the settings for each section
    public array $sectionSettings = [];

    // mutex timeout for criticalcss.com API locking (in seconds)
    public int $mutexTimeout = 30;

    // maximum number of retries for retryable job failures (mutex locks, network issues, etc.)
    public int $maxRetries = 3;

    // base delay in seconds for retry exponential backoff
    public int $retryBaseDelay = 30;
}
