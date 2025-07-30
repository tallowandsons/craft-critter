<?php

use tallowandsons\critter\Critter;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\models\UrlModel;

describe('Cache Key Debug Tests', function () {

    it('shows cache keys for different scenarios', function () {
        // Create mock entries for testing
        $entry1 = new \craft\elements\Entry();
        $entry1->id = 123;
        $entry1->siteId = 1;

        $entry2 = new \craft\elements\Entry();
        $entry2->id = 456;
        $entry2->siteId = 1;

        // Test with a URL that matches entry 1
        $url1 = new UrlModel('some/path?foo=bar', 1);
        $url1->setMatchedElement($entry1);
        $cssRequest1 = (new CssRequest())->setRequestUrl($url1);

        echo "\n=== URL 1: some/path?foo=bar (Entry 123) ===\n";
        echo "Cache Key: " . $cssRequest1->getKey() . "\n";
        echo "Tag: " . $cssRequest1->getTag() . "\n";
        echo "Matched Element: " . ($url1->getMatchedElement() ? get_class($url1->getMatchedElement()) . " ID: " . $url1->getMatchedElement()->id : "null") . "\n";

        // Test with a different URL but same entry
        $url2 = new UrlModel('different/path?foo=bar', 1);
        $url2->setMatchedElement($entry1); // Same entry!
        $cssRequest2 = (new CssRequest())->setRequestUrl($url2);

        echo "\n=== URL 2: different/path?foo=bar (Same Entry 123) ===\n";
        echo "Cache Key: " . $cssRequest2->getKey() . "\n";
        echo "Tag: " . $cssRequest2->getTag() . "\n";
        echo "Matched Element: " . ($url2->getMatchedElement() ? get_class($url2->getMatchedElement()) . " ID: " . $url2->getMatchedElement()->id : "null") . "\n";

        // Test with a different entry
        $url3 = new UrlModel('another/path?foo=bar', 1);
        $url3->setMatchedElement($entry2); // Different entry
        $cssRequest3 = (new CssRequest())->setRequestUrl($url3);

        echo "\n=== URL 3: another/path?foo=bar (Entry 456) ===\n";
        echo "Cache Key: " . $cssRequest3->getKey() . "\n";
        echo "Tag: " . $cssRequest3->getTag() . "\n";
        echo "Matched Element: " . ($url3->getMatchedElement() ? get_class($url3->getMatchedElement()) . " ID: " . $url3->getMatchedElement()->id : "null") . "\n";

        // Test with no entry (URL fallback)
        $url4 = new UrlModel('definitely/not/an/entry/path/xyz123?foo=bar', 1);
        // No setMatchedElement call, so it should fall back to URL-based
        $cssRequest4 = (new CssRequest())->setRequestUrl($url4);

        echo "\n=== URL 4: definitely/not/an/entry/path/xyz123?foo=bar (No Entry) ===\n";
        echo "Cache Key: " . $cssRequest4->getKey() . "\n";
        echo "Tag: " . $cssRequest4->getTag() . "\n";
        echo "Matched Element: " . ($url4->getMatchedElement() ? get_class($url4->getMatchedElement()) . " ID: " . $url4->getMatchedElement()->id : "null") . "\n";

        echo "\n=== Comparison ===\n";
        echo "Key 1 == Key 2 (same entry): " . ($cssRequest1->getKey() === $cssRequest2->getKey() ? "YES ✅" : "NO ❌") . "\n";
        echo "Key 1 == Key 3 (diff entry): " . ($cssRequest1->getKey() === $cssRequest3->getKey() ? "YES" : "NO ✅") . "\n";
        echo "Key 1 == Key 4 (no entry): " . ($cssRequest1->getKey() === $cssRequest4->getKey() ? "YES" : "NO ✅") . "\n";
        echo "Tag 1 == Tag 2 (same entry): " . ($cssRequest1->getTag() === $cssRequest2->getTag() ? "YES ✅" : "NO ❌") . "\n";

        // Test our expectations
        expect($cssRequest1->getKey())->toBe($cssRequest2->getKey()); // Same entry should have same cache key
        expect($cssRequest1->getKey())->not->toBe($cssRequest3->getKey()); // Different entries should have different keys
        expect($cssRequest1->getTag())->toBe($cssRequest2->getTag()); // Same entry should have same tag
    });
});
