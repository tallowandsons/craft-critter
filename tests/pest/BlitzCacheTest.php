<?php

use tallowandsons\critter\drivers\caches\BlitzCache;
use tallowandsons\critter\models\UrlModel;

/**
 * Unit tests for BlitzCache class
 *
 * These tests focus on the BlitzCache class behavior, specifically testing
 * the new URL pattern generation and cache behavior options.
 */

describe('BlitzCache', function () {

    describe('class structure', function () {
        it('is instantiable', function () {
            $blitzCache = new BlitzCache();
            expect($blitzCache)->toBeInstanceOf(BlitzCache::class);
        });

        it('has the correct handle', function () {
            $blitzCache = new BlitzCache();
            expect($blitzCache->handle)->toBe('blitz');
        });

        it('has the expected cache behavior constants', function () {
            expect(BlitzCache::CACHE_BEHAVIOUR_CLEAR_URLS)->toBe('clearUrls');
            expect(BlitzCache::CACHE_BEHAVIOUR_EXPIRE_URLS)->toBe('expireUrls');
            expect(BlitzCache::CACHE_BEHAVIOUR_REFRESH_URLS)->toBe('refreshUrls');
        });
    });

    describe('display name', function () {
        it('returns correct display name', function () {
            expect(BlitzCache::displayName())->toBe('Blitz');
        });

        it('returns a string', function () {
            expect(BlitzCache::displayName())->toBeString();
        });
    });

    describe('cache behavior options', function () {
        it('provides correct cache behavior options', function () {
            $options = BlitzCache::getCacheBehaviourOptions();

            expect($options)->toHaveCount(3);
            expect($options)->toBeArray();
        });

        it('includes all expected cache behaviors', function () {
            $options = BlitzCache::getCacheBehaviourOptions();
            $values = array_column($options, 'value');

            expect($values)->toContain(BlitzCache::CACHE_BEHAVIOUR_CLEAR_URLS);
            expect($values)->toContain(BlitzCache::CACHE_BEHAVIOUR_EXPIRE_URLS);
            expect($values)->toContain(BlitzCache::CACHE_BEHAVIOUR_REFRESH_URLS);
        });

        it('has proper option structure', function () {
            $options = BlitzCache::getCacheBehaviourOptions();

            foreach ($options as $option) {
                expect($option)->toHaveKey('label');
                expect($option)->toHaveKey('value');
                expect($option['label'])->toBeString();
                expect($option['value'])->toBeString();
            }
        });
    });

    describe('settings', function () {
        it('returns correct settings structure', function () {
            $blitzCache = new BlitzCache();
            $settings = $blitzCache->getSettings();

            expect($settings)->toBeArray();
            expect($settings)->toHaveKey('cacheBehaviourOptions');
            expect($settings)->toHaveKey('settings');
            expect($settings)->toHaveKey('config');
            expect($settings)->toHaveKey('pluginHandle');
        });

        it('includes cache behavior options in settings', function () {
            $blitzCache = new BlitzCache();
            $settings = $blitzCache->getSettings();

            expect($settings['cacheBehaviourOptions'])->toBeArray();
            expect($settings['cacheBehaviourOptions'])->toHaveCount(3);
        });
    });

    describe('cache behavior configuration', function () {
        it('defaults to refresh URLs behavior', function () {
            $blitzCache = new BlitzCache();
            expect($blitzCache->cacheBehaviour)->toBe(BlitzCache::CACHE_BEHAVIOUR_REFRESH_URLS);
        });
    });
});
