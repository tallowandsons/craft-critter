<?php

use mijewe\critter\Critter;
use mijewe\critter\drivers\caches\CacheInterface;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\Settings;
use mijewe\critter\models\UrlModel;
use mijewe\critter\services\CacheService;

describe('CacheService Feature Tests', function () {

    beforeEach(function () {
        $this->plugin = Critter::getInstance();
        $this->url = new UrlModel('https://example.com/test-page');
    });

    describe('Service Initialization', function () {

        it('initializes with a cache driver', function () {
            $cacheService = $this->plugin->cache;

            expect($cacheService)->toBeInstanceOf(CacheService::class);
            expect($cacheService->cache)->toBeInstanceOf(CacheInterface::class);
        });

        it('cache has required interface methods', function () {
            $cacheService = $this->plugin->cache;

            expect($cacheService->cache)->toBeInstanceOf(CacheInterface::class);
            expect(method_exists($cacheService->cache, 'resolveCache'))->toBeTrue();
        });
    });

    describe('Cache Resolution by Mode', function () {

        it('resolves cache for entry mode', function () {
            $cacheService = $this->plugin->cache;

            $cssRequestMock = $this->createMock(CssRequest::class);
            $cssRequestMock->method('getUrl')->willReturn($this->url);
            $cssRequestMock->method('getMode')->willReturn(Settings::MODE_ENTRY);

            // Should not throw any exceptions
            $cacheService->resolveCache($cssRequestMock);

            expect(true)->toBeTrue();
        });

        it('resolves cache for section mode with valid section', function () {
            $cacheService = $this->plugin->cache;

            // Create a mock URL that has a section
            $urlMock = $this->createMock(UrlModel::class);
            $urlMock->method('getSectionHandle')->willReturn('blog');
            $urlMock->method('getAbsoluteUrl')->willReturn('https://example.com/test-entry');

            $cssRequestMock = $this->createMock(CssRequest::class);
            $cssRequestMock->method('getUrl')->willReturn($urlMock);
            $cssRequestMock->method('getMode')->willReturn(Settings::MODE_SECTION);

            // Should not throw any exceptions
            $cacheService->resolveCache($cssRequestMock);

            expect(true)->toBeTrue();
        });

        it('throws exception for invalid mode', function () {
            $cacheService = $this->plugin->cache;

            $cssRequestMock = $this->createMock(CssRequest::class);
            $cssRequestMock->method('getUrl')->willReturn($this->url);
            $cssRequestMock->method('getMode')->willReturn('invalid_mode');

            expect(fn() => $cacheService->resolveCache($cssRequestMock))
                ->toThrow(Exception::class, 'Could not resolve cache; invalid mode: invalid_mode');
        });
    });

    describe('Integration Tests', function () {

        it('works with current configuration', function () {
            $cacheService = $this->plugin->cache;

            expect($cacheService)->toBeInstanceOf(CacheService::class);
            expect($cacheService->cache)->toBeInstanceOf(CacheInterface::class);

            // Verify we can identify the cache type
            $cacheClass = get_class($cacheService->cache);
            expect($cacheClass)->toBeString();
        });

        it('can resolve cache for real UrlModel', function () {
            $cacheService = $this->plugin->cache;
            $url = new UrlModel('/');

            $cssRequestMock = $this->createMock(CssRequest::class);
            $cssRequestMock->method('getUrl')->willReturn($url);
            $cssRequestMock->method('getMode')->willReturn(Settings::MODE_ENTRY);

            // Should work with real UrlModel
            $cacheService->resolveCache($cssRequestMock);

            expect(true)->toBeTrue();
        });
    });
});
