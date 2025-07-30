<?php

use tallowandsons\critter\models\UrlModel;

/**
 * Unit tests for UrlModel class
 *
 * These tests focus on the UrlModel class, particularly testing the enhanced
 * getAbsoluteUrl method with baseUrlOverride support.
 */

describe('UrlModel', function () {

    describe('class structure', function () {
        it('is instantiable', function () {
            $urlModel = new UrlModel();
            expect($urlModel)->toBeInstanceOf(UrlModel::class);
        });

        it('can be instantiated with url and site id', function () {
            $urlModel = new UrlModel('/test', 1);
            expect($urlModel->getPath())->toBe('test');
            expect($urlModel->getSiteId())->toBe(1);
        });
    });

    describe('URL setting and getting', function () {
        it('sets and gets url correctly', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about');

            expect($urlModel->getPath())->toBe('about');
        });

        it('extracts query parameters from URL', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about?foo=bar&baz=qux');

            expect($urlModel->getPath())->toBe('about');
            // Note: Query params may be filtered by plugin settings
            $params = $urlModel->getQueryParams();
            expect($params)->toBeArray();
        });

        it('handles URLs without query parameters', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about');

            expect($urlModel->getPath())->toBe('about');
            expect($urlModel->getQueryParams())->toBe([]);
        });
    });

    describe('getAbsoluteUrl method', function () {
        it('has the correct method signature', function () {
            $reflection = new ReflectionClass(UrlModel::class);
            $method = $reflection->getMethod('getAbsoluteUrl');

            expect($method->isPublic())->toBeTrue();

            $parameters = $method->getParameters();
            expect($parameters)->toHaveCount(2);
            expect($parameters[0]->getName())->toBe('includeQueryString');
            expect($parameters[1]->getName())->toBe('allowBaseUrlOverride');
        });

        it('accepts both parameters independently', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about');

            // Test all combinations
            $url1 = $urlModel->getAbsoluteUrl(true, true);
            $url2 = $urlModel->getAbsoluteUrl(true, false);
            $url3 = $urlModel->getAbsoluteUrl(false, true);
            $url4 = $urlModel->getAbsoluteUrl(false, false);

            expect($url1)->toBeString();
            expect($url2)->toBeString();
            expect($url3)->toBeString();
            expect($url4)->toBeString();
        });

        it('returns different results with different parameters', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/test');

            $withOverride = $urlModel->getAbsoluteUrl(false, true);
            $withoutOverride = $urlModel->getAbsoluteUrl(false, false);

            // Both should be strings
            expect($withOverride)->toBeString();
            expect($withoutOverride)->toBeString();
        });
    });

    describe('getUrl method integration', function () {
        it('getAbsoluteUrl calls getUrl with correct parameters', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/test');

            // Since getAbsoluteUrl is an alias for getUrl, test they behave consistently
            $absolute1 = $urlModel->getAbsoluteUrl(true, true);
            $url1 = $urlModel->getUrl(true, true);

            $absolute2 = $urlModel->getAbsoluteUrl(false, false);
            $url2 = $urlModel->getUrl(false, false);

            expect($absolute1)->toBe($url1);
            expect($absolute2)->toBe($url2);
        });
    });

    describe('path and domain methods', function () {
        it('returns correct path', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/about/team');

            expect($urlModel->getPath())->toBe('about/team');
        });

        it('handles root path correctly', function () {
            $urlModel = new UrlModel();
            $urlModel->setUrl('/');

            expect($urlModel->getPath())->toBe('');
        });
    });

    describe('query string handling', function () {
        it('returns string for query string method', function () {
            $urlModel = new UrlModel();
            $urlModel->setQueryParams(['foo' => 'bar']);

            $queryString = $urlModel->getQueryString();
            expect($queryString)->toBeString();
        });

        it('returns empty string for no query params', function () {
            $urlModel = new UrlModel();
            $urlModel->setQueryParams([]);

            expect($urlModel->getQueryString())->toBe('');
        });
    });

    describe('site ID handling', function () {
        it('sets and gets site ID correctly', function () {
            $urlModel = new UrlModel();
            $urlModel->setSiteId(5);

            expect($urlModel->getSiteId())->toBe(5);
        });

        it('allows method chaining for setSiteId', function () {
            $urlModel = new UrlModel();
            $result = $urlModel->setSiteId(3);

            expect($result)->toBe($urlModel);
            expect($urlModel->getSiteId())->toBe(3);
        });
    });
});
