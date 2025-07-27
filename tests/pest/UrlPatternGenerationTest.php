<?php

use mijewe\critter\models\UrlModel;

/**
 * Integration tests for URL pattern generation
 *
 * These tests focus on how UrlModel generates patterns for cache operations,
 * particularly the behavior that creates both exact URLs and wildcard patterns.
 */

describe('URL Pattern Generation', function () {

    describe('UrlModel for cache operations', function () {
        it('generates consistent URLs for cache operations', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about');

            // Test that getAbsoluteUrl without parameters returns consistent results
            $url1 = $urlModel->getAbsoluteUrl(false, false);
            $url2 = $urlModel->getAbsoluteUrl(false, false);

            expect($url1)->toBe($url2);
            expect($url1)->toBeString();
        });

        it('handles query parameters correctly in cache context', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about?foo=bar');

            // URL without query string (for exact cache match)
            $urlWithoutQuery = $urlModel->getAbsoluteUrl(false, false);

            // URL with query string
            $urlWithQuery = $urlModel->getAbsoluteUrl(true, false);

            expect($urlWithoutQuery)->toBeString();
            expect($urlWithQuery)->toBeString();

            // The URL without query should not contain query params
            expect($urlWithoutQuery)->not->toContain('foo=bar');
        });

        it('strips base URL override when requested', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/contact');

            // Test both with and without base URL override
            $urlWithOverride = $urlModel->getAbsoluteUrl(false, true);
            $urlWithoutOverride = $urlModel->getAbsoluteUrl(false, false);

            expect($urlWithOverride)->toBeString();
            expect($urlWithoutOverride)->toBeString();
        });
    });

    describe('URL patterns for wildcard matching', function () {
        it('creates predictable base URLs for wildcard patterns', function () {
            $testUrls = [
                '/about',
                '/contact',
                '/products/category',
                '/blog/post-title'
            ];

            foreach ($testUrls as $testUrl) {
                $urlModel = new UrlModel();
                $urlModel->setUrl($testUrl);

                $baseUrl = $urlModel->getAbsoluteUrl(false, false);

                // Should not be empty
                expect($baseUrl)->not->toBeEmpty();

                // Should be consistent when called multiple times
                $baseUrl2 = $urlModel->getAbsoluteUrl(false, false);
                expect($baseUrl)->toBe($baseUrl2);
            }
        });

        it('supports URL pattern generation workflow', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/api/endpoint?version=2&format=json');

            // Simulate the pattern generation used in BlitzCache
            $baseUrl = $urlModel->getAbsoluteUrl(false, false);

            // These would be the patterns generated for Blitz
            $exactPattern = $baseUrl;
            $wildcardPattern = $baseUrl . '?*';

            expect($exactPattern)->not->toContain('?');
            expect($wildcardPattern)->toEndWith('?*');
            expect($wildcardPattern)->toContain($baseUrl);
        });
    });

    describe('edge cases and error handling', function () {
        it('handles empty URLs gracefully', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('');

            $url = $urlModel->getAbsoluteUrl(false, false);
            expect($url)->toBeString();
        });

        it('handles root URL correctly', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/');

            $url = $urlModel->getAbsoluteUrl(false, false);
            expect($url)->toBeString();
        });

        it('handles URLs with special characters', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/search?q=hello%20world&category=news');

            $baseUrl = $urlModel->getAbsoluteUrl(false, false);
            $fullUrl = $urlModel->getAbsoluteUrl(true, false);

            expect($baseUrl)->toBeString();
            expect($fullUrl)->toBeString();
            expect($baseUrl)->not->toContain('hello%20world');
        });
    });

    describe('consistency across instances', function () {
        it('produces same results for identical URLs', function () {
            $urlModel1 = new UrlModel();
            $urlModel1->setUrl('/same-path');

            $urlModel2 = new UrlModel();
            $urlModel2->setUrl('/same-path');

            $url1 = $urlModel1->getAbsoluteUrl(false, false);
            $url2 = $urlModel2->getAbsoluteUrl(false, false);

            expect($url1)->toBe($url2);
        });

        it('handles different site IDs correctly', function () {
            $urlModel1 = new UrlModel('/test', 1);
            $urlModel2 = new UrlModel('/test', 2);

            $url1 = $urlModel1->getAbsoluteUrl(false, false);
            $url2 = $urlModel2->getAbsoluteUrl(false, false);

            // URLs might be different due to different sites
            expect($url1)->toBeString();
            expect($url2)->toBeString();
        });
    });
});
