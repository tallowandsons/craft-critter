<?php

namespace mijewe\critter\helpers;

use mijewe\critter\Critter;
use mijewe\critter\drivers\caches\BlitzCache;
use mijewe\critter\drivers\caches\CacheInterface;
use mijewe\critter\drivers\caches\NoCache;
use mijewe\critter\events\RegisterCachesEvent;
use yii\base\Event;

class CacheHelper
{
    /**
     * @event RegisterCachesEvent
     */
    public const EVENT_REGISTER_CACHES = 'registerCaches';

    /**
     * @var array Registered cache classes
     */
    private static array $_caches = [];

    /**
     * @var bool Whether caches have been registered
     */
    private static bool $_cachesRegistered = false;

    /**
     * Returns an array of all available caches.
     * This includes the default caches and any custom caches
     * that are registered via events.
     * Note: this returns an array of instances. If you want the class names,
     * use `getCacheTypes()` instead.
     */
    static function getCacheInstances(): array
    {
        $caches = [];
        foreach (self::getCacheTypes() as $cacheType) {
            if (self::isValidCache($cacheType)) {
                $caches[] = new $cacheType();
            }
        }
        return $caches;
    }

    /**
     * Returns an array of all available cache types.
     * This includes the default caches and any custom caches
     * that are registered via events.
     * Note: this returns an array of classes.
     * If you want the instances, use `getCacheInstances()` instead.
     */
    static function getCacheTypes(): array
    {
        if (!self::$_cachesRegistered) {
            self::_registerCaches();
        }

        return self::$_caches;
    }

    /**
     * Returns an array of all available cache types as <select> options.
     */
    static function getCacheTypesAsSelectOptions(): array
    {
        return array_map(function ($cacheType) {
            return [
                'label' => $cacheType::displayName(),
                'value' => $cacheType,
            ];
        }, self::getCacheTypes());
    }

    /**
     * Checks if the given cache type is a valid cache.
     */
    static function isValidCache(string $cacheType): bool
    {
        return is_subclass_of($cacheType, CacheInterface::class);
    }

    /**
     * Registers a cache class
     */
    public static function registerCache(string $cacheClass): void
    {
        if (self::isValidCache($cacheClass) && !in_array($cacheClass, self::$_caches)) {
            self::$_caches[] = $cacheClass;
        }
    }

    /**
     * Registers the default caches and fires an event to allow other plugins to register custom caches
     */
    private static function _registerCaches(): void
    {
        // Register default caches
        $defaultCaches = [
            NoCache::class,
        ];

        // Only add BlitzCache if Blitz plugin is available
        if (class_exists('putyourlightson\blitz\Blitz')) {
            $defaultCaches[] = BlitzCache::class;
        }

        // Merge default caches with existing ones
        if (!isset(self::$_caches)) {
            self::$_caches = [];
        }

        foreach ($defaultCaches as $cache) {
            self::registerCache($cache);
        }

        // Add whatever cache is currently selected to the list of cache types,
        // as it may not be in the list above if the config file is
        // set up with a custom cache.
        $currentCacheType = Critter::getInstance()->settings->cacheType;
        if ($currentCacheType && self::isValidCache($currentCacheType)) {
            self::registerCache($currentCacheType);
        }

        // Fire event to allow other plugins to register custom caches
        $event = new RegisterCachesEvent([
            'caches' => self::$_caches
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_CACHES, $event);

        // Add any caches that were registered via the event
        foreach ($event->caches as $cache) {
            self::registerCache($cache);
        }

        self::$_cachesRegistered = true;
    }
}
