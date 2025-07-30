<?php

/**
 * Critter config.php
 *
 * This file exists only as a template for the Critter settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'critter.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 *
 * @see models\Settings.php for all available options and documentation.
 */

// use tallowandsons\critter\models\Settings;
// use tallowandsons\critter\drivers\caches\NoCache;
// use tallowandsons\critter\generators\CriticalCssDotComGenerator;
// use tallowandsons\critter\generators\NoGenerator;
// use tallowandsons\critter\storage\CraftCacheStorage;

return [
    // Enable or disable automatic rendering of critical CSS
    // 'autoRenderEnabled' => true,

    // Attributes to add to the <style> tag where critical CSS is inserted
    // 'styleTagAttributes' => [],

    // Generator class to use for generating critical CSS
    // 'generatorType' => CriticalCssDotComGenerator::class, // Default

    // Settings for the generator
    // 'generatorSettings' => [],

    // Which default generators should be registered (null = use default list)
    // This controls which generators appear in the admin UI
    // 'generators' => null,

    // Examples:
    // To include the CLI generator (advanced users only):
    // 'generators' => [
    //     \tallowandsons\critter\generators\NoGenerator::class,
    //     \tallowandsons\critter\generators\CriticalCssDotComGenerator::class,
    //     \tallowandsons\critter\generators\CriticalCssCliGenerator::class,
    // ],

    // To include the DummyGenerator for testing:
    // 'generators' => [
    //     \tallowandsons\critter\generators\NoGenerator::class,
    //     \tallowandsons\critter\generators\CriticalCssDotComGenerator::class,
    //     \tallowandsons\critter\generators\DummyGenerator::class,
    // ],

    // Storage class to use for storing critical CSS
    // 'storageType' => CraftCacheStorage::class,

    // Cache class to use for caching critical CSS
    // 'cacheType' => NoCache::class,

    // Settings for the cache
    // 'cacheSettings' => [],

    // Query string parameters that should be treated as unique URLs
    // 'uniqueQueryParams' => [],

    // Base URL override for critical CSS generation (useful for staging, etc.)
    // 'baseUrlOverride' => null,

    // Default mode for critical CSS generation: 'entry' or 'section'
    // Options: Settings::MODE_ENTRY, Settings::MODE_SECTION
    // 'defaultMode' => Settings::MODE_SECTION,

    // Section-specific settings (per section handle)
    // 'sectionSettings' => [],

    // Mutex timeout for API locking (in seconds)
    // 'mutexTimeout' => 30,

    // Maximum number of retries for retryable job failures
    // 'maxRetries' => 3,

    // Base delay in seconds for retry exponential backoff
    // 'retryBaseDelay' => 30,

    // Enable debug logging to storage/logs/critter.log
    // 'enableDebugLogging' => false,

    // What to do when an entry is saved
    // Options: Settings::ENTRY_SAVE_DO_NOTHING, Settings::ENTRY_SAVE_EXPIRE_CSS
    // 'onEntrySaveBehaviour' => Settings::ENTRY_SAVE_EXPIRE_CSS,

    // How to handle regeneration of expired Critical CSS
    // Options: Settings::REGENERATE_MANUALLY, Settings::REGENERATE_ON_REQUEST
    // 'regenerateExpiredCss' => Settings::REGENERATE_MANUALLY,
];
