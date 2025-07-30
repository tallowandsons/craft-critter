<?php

use tallowandsons\critter\Critter;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\models\UrlModel;

describe('CssRequest Tests', function () {

    beforeEach(function () {
        // Configure allowed query parameters for testing
        $settings = Critter::getInstance()->settings;
        $settings->uniqueQueryParams = [
            [
                'enabled' => '1',
                'param' => 'foo'
            ],
            [
                'enabled' => '1',
                'param' => 'test'
            ]
        ];
    });

    describe('Cache Key Consistency', function () {

        it('maintains consistent cache key for same entry across URI changes', function () {
            // Create a mock entry
            $entry = new \craft\elements\Entry();
            $entry->id = 123;
            $entry->siteId = 1;

            // Create two different URLs but with the same entry (simulating URI change)
            $url1 = new UrlModel('old/path?foo=bar', 1);
            $url1->setMatchedElement($entry);

            $url2 = new UrlModel('new/path?foo=bar', 1);
            $url2->setMatchedElement($entry); // Same entry

            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);

            // Cache keys should be identical even though URIs are different
            expect($cssRequest1->getKey())->toBe($cssRequest2->getKey());
            expect($cssRequest1->getKey())->toBe('mode:entry|site:1|entry:123|query:foo=bar');
        });

        it('generates different cache keys for different entries', function () {
            $entry1 = new \craft\elements\Entry();
            $entry1->id = 123;
            $entry1->siteId = 1;

            $entry2 = new \craft\elements\Entry();
            $entry2->id = 456;
            $entry2->siteId = 1;

            $url1 = new UrlModel('path1?foo=bar', 1);
            $url1->setMatchedElement($entry1);

            $url2 = new UrlModel('path2?foo=bar', 1);
            $url2->setMatchedElement($entry2);

            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);

            // Cache keys should be different for different entries
            expect($cssRequest1->getKey())->not->toBe($cssRequest2->getKey());
            expect($cssRequest1->getKey())->toBe('mode:entry|site:1|entry:123|query:foo=bar');
            expect($cssRequest2->getKey())->toBe('mode:entry|site:1|entry:456|query:foo=bar');
        });

        it('includes query parameters in cache key', function () {
            $entry = new \craft\elements\Entry();
            $entry->id = 123;
            $entry->siteId = 1;

            $url1 = new UrlModel('path?foo=bar', 1);
            $url1->setMatchedElement($entry);

            $url2 = new UrlModel('path?foo=baz', 1);
            $url2->setMatchedElement($entry);

            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);

            // Cache keys should be different for different query parameters
            expect($cssRequest1->getKey())->not->toBe($cssRequest2->getKey());
            expect($cssRequest1->getKey())->toBe('mode:entry|site:1|entry:123|query:foo=bar');
            expect($cssRequest2->getKey())->toBe('mode:entry|site:1|entry:123|query:foo=baz');
        });

        it('falls back to URL for non-entry URLs', function () {
            $url = new UrlModel('some/custom/path?foo=bar', 1);
            // No setMatchedElement call, so getMatchedElement() returns null

            $cssRequest = (new CssRequest())->setRequestUrl($url);

            // Should fall back to full URL since there's no entry
            expect($cssRequest->getKey())->toBe($url->getAbsoluteUrl());
        });
    });

    describe('Tag Generation', function () {

        it('uses Tag::fromEntry() for entry tags', function () {
            $entry = new \craft\elements\Entry();
            $entry->id = 789;
            $entry->siteId = 1;

            $url = new UrlModel('test/path?foo=bar', 1);
            $url->setMatchedElement($entry);

            $cssRequest = (new CssRequest())->setRequestUrl($url);

            expect($cssRequest->getTag())->toBe('entry:789');
        });

        it('falls back to URL hash for non-entry URLs', function () {
            $url = new UrlModel('some/custom/path?foo=bar', 1);
            // No setMatchedElement call, so getMatchedElement() returns null

            $cssRequest = (new CssRequest())->setRequestUrl($url);

            $expectedTag = 'url:' . md5($url->getAbsoluteUrl());
            expect($cssRequest->getTag())->toBe($expectedTag);
        });

        it('uses consistent entry tag format', function () {
            $entry = new \craft\elements\Entry();
            $entry->id = 999;
            $entry->siteId = 1;

            $url = new UrlModel('test/path', 1);
            $url->setMatchedElement($entry);

            $cssRequest = (new CssRequest())->setRequestUrl($url);

            expect($cssRequest->getTag())->toBe('entry:999');
        });
    });
});
