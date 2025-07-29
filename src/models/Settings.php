<?php

namespace mijewe\critter\models;

use craft\base\Model;
use mijewe\critter\drivers\caches\NoCache;
use mijewe\critter\generators\NoGenerator;
use mijewe\critter\storage\CraftCacheStorage;

/**
 * Critter settings
 */
class Settings extends Model
{

    const MODE_ENTRY = 'entry';
    const MODE_SECTION = 'section';

    // Entry save behaviour options
    const ENTRY_SAVE_DO_NOTHING = 'doNothing';
    const ENTRY_SAVE_EXPIRE_CSS = 'expireRelatedCss';

    // Regenerate expired CSS options
    const REGENERATE_MANUALLY = 'manually';
    const REGENERATE_ON_REQUEST = 'onRequest';

    // whether or not to automatically render the critical css
    public bool $autoRenderEnabled = true;

    // what attributes to pass to the style tag where the critical css is inserted
    public array $styleTagAttributes = [];

    // which generator type to use
    public string $generatorType = NoGenerator::class;

    // the settings for the generator
    public array $generatorSettings = [];

    // which default generators should be registered (null = use default list)
    // this allows developers to control which generators appear in the UI
    public ?array $generators = null;

    // which storage type to use
    public string $storageType = CraftCacheStorage::class;

    // which cache type to use
    public ?string $cacheType = NoCache::class;

    // the settings for the cache
    public array $cacheSettings = [];

    // which query string parameters are to be treated as unique urls
    public array $uniqueQueryParams = [];

    // a base URL override for the Critter
    // this is useful if you want to generate critical css for a different domain
    // than the one the site is running on, for example on a staging environment.
    public ?string $baseUrlOverride = null;

    // what the default mode should be.
    // this will determine whether critical css
    // is generated for each entry or each section
    public string $defaultMode = self::MODE_SECTION;

    // the settings for each section
    public array $sectionSettings = [];

    // mutex timeout for criticalcss.com API locking (in seconds)
    public int $mutexTimeout = 30;

    // maximum number of retries for retryable job failures (mutex locks, network issues, etc.)
    public int $maxRetries = 3;

    // base delay in seconds for retry exponential backoff
    public int $retryBaseDelay = 30;

    // whether to enable debug logging to storage/logs/critter.log
    public bool $enableDebugLogging = false;

    // what to do when an entry is saved (expire critical CSS or do nothing)
    public string $onEntrySaveBehaviour = self::ENTRY_SAVE_EXPIRE_CSS;

    // how to handle regeneration of expired critical CSS
    public string $regenerateExpiredCss = self::REGENERATE_MANUALLY;

    // developer mode - enables advanced/experimental features (no UI, config file only)
    public bool $developerMode = false;
}
