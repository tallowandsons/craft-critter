<?php

namespace tallowandsons\critter\models;

use Craft;
use craft\base\Model;
use tallowandsons\critter\drivers\caches\BlitzCache;
use tallowandsons\critter\drivers\caches\NoCache;
use tallowandsons\critter\generators\CriticalCssDotComGenerator;
use tallowandsons\critter\storage\CraftCacheStorage;

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
    public string $generatorType = CriticalCssDotComGenerator::class;

    // the settings for the generator
    public array $generatorSettings = [];

    // which default generators should be registered (null = use default list)
    // this allows developers to control which generators appear in the UI
    public ?array $generators = null;

    // which storage type to use
    public string $storageType = CraftCacheStorage::class;

    // which cache type to use
    public ?string $cacheType = null;

    // the settings for the cache
    public array $cacheSettings = [];

    // which query string parameters are to be treated as unique urls
    public array $uniqueQueryParams = [];

    // a base URL override for the Critter
    // this is useful if you want to generate critical css for a different domain
    // than the one the site is running on, for example on a staging environment.
    public ?string $baseUrlOverride = null;

    // Optional HTTP Basic Auth credentials used when Critical CSS generator
    // needs to fetch pages protected by basic authentication.
    // These support environment variables (e.g. "$BASIC_AUTH_USER").
    public ?string $basicAuthUsername = null;
    public ?string $basicAuthPassword = null;

    // what the default mode should be.
    // this will determine whether critical css
    // is generated for each entry or each section
    public string $defaultMode = self::MODE_SECTION;

    // the settings for each section
    public array $sectionSettings = [];

    // maximum number of retries for retryable job failures (mutex locks, network issues, etc.)
    public int $maxRetries = 3;

    // time to reserve (TTR) for queue jobs in seconds - maximum execution time per job attempt
    public int $jobTtr = 300;

    // whether to enable debug logging to storage/logs/critter.log
    public bool $enableDebugLogging = false;

    // what to do when an entry is saved (expire critical CSS or do nothing)
    public string $onEntrySaveBehaviour = self::ENTRY_SAVE_EXPIRE_CSS;

    // how to handle regeneration of expired critical CSS
    public string $regenerateExpiredCss = self::REGENERATE_MANUALLY;

    // developer mode - enables advanced/experimental features (no UI, config file only)
    public bool $developerMode = false;

    // fallback CSS file path - file to read CSS from when no critical CSS is cached
    // should be an absolute path to a CSS file on the server
    public ?string $fallbackCssFilePath = null;

    // URL patterns to exclude from critical CSS generation
    public array $excludePatterns = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Set conditional defaults based on installed plugins
        $this->setConditionalDefaults();
    }

    /**
     * Set conditional default values based on available plugins
     */
    protected function setConditionalDefaults(): void
    {
        // Check if Blitz plugin is installed (only in Craft context)
        $blitzIsInstalled = false;
        try {
            $blitzIsInstalled = \Craft::$app && \Craft::$app->plugins && \Craft::$app->plugins->getPlugin('blitz') !== null;
        } catch (\Throwable $e) {
            // Not in Craft context, use default fallback
        }

        // Set cache defaults based on Blitz availability
        if ($blitzIsInstalled && $this->cacheType === null) {
            $this->cacheType = BlitzCache::class;

            // Set default cache behavior to refresh URLs if not already set
            if (empty($this->cacheSettings['cacheBehaviour'])) {
                $this->cacheSettings['cacheBehaviour'] = BlitzCache::CACHE_BEHAVIOUR_REFRESH_URLS;
            }
        } elseif (!$blitzIsInstalled && $this->cacheType === null) {
            $this->cacheType = NoCache::class;
        }
    }
}
