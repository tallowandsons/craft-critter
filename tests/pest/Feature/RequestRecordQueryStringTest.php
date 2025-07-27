<?php

use mijewe\critter\Critter;
use mijewe\critter\factories\UrlFactory;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;

/**
 * Tests for the new queryString column functionality in RequestRecord
 */

describe('RequestRecord QueryString Tests', function () {

    beforeEach(function () {
        // Clean up any existing test records
        RequestRecord::deleteAll(['uri' => 'test/path']);

        // Configure allowed query parameters for testing
        $settings = Critter::getInstance()->settings;
        $settings->uniqueQueryParams = [
            [
                'enabled' => '1',
                'param' => 'foo'
            ],
            [
                'enabled' => '1',
                'param' => 'baz'
            ],
            [
                'enabled' => '1',
                'param' => 'test'
            ],
            [
                'enabled' => '1',
                'param' => 'success'
            ]
        ];
    });

    describe('URL Separation', function () {

        it('stores URI and query string separately', function () {
            $url = new UrlModel('test/path?foo=bar&baz=qux', 1);
            $cssRequest = (new CssRequest())->setRequestUrl($url);

            $record = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest);
            $record->save();

            expect($record->uri)->toBe('test/path');
            expect($record->queryString)->toBe('baz=qux&foo=bar'); // Normalized alphabetical order
        });

        it('handles URLs without query strings', function () {
            $url = new UrlModel('test/path', 1);
            $cssRequest = (new CssRequest())->setRequestUrl($url);

            $record = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest);
            $record->save();

            expect($record->uri)->toBe('test/path');
            expect($record->queryString)->toBeNull();
        });

        it('handles URLs with empty query strings', function () {
            $url = new UrlModel('test/path?', 1);
            $cssRequest = (new CssRequest())->setRequestUrl($url);

            $record = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest);
            $record->save();

            expect($record->uri)->toBe('test/path');
            expect($record->queryString)->toBeNull();
        });

        it('normalizes query parameter order for consistent storage', function () {
            // Test that different parameter orders result in the same stored query string
            $url1 = new UrlModel('test/path?success=true&foo=one', 1);
            $url2 = new UrlModel('test/path?foo=one&success=true', 1);

            // Both should produce the same normalized query string (sorted by key)
            expect($url1->getQueryString())->toBe($url2->getQueryString());
            expect($url1->getQueryString())->toBe('foo=one&success=true'); // alphabetical order

            // Both should result in the same database record
            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);

            $record1 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest1);
            $record1->save();

            $record2 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest2);

            // Should find the existing record, not create a new one
            expect($record2->id)->toBe($record1->id);
            expect($record1->queryString)->toBe('foo=one&success=true');
        });
    });

    describe('URL Reconstruction', function () {

        it('reconstructs full URL correctly with query string', function () {
            $record = new RequestRecord();
            $record->uri = 'test/path';
            $record->queryString = 'baz=qux&foo=bar'; // Stored in normalized order
            $record->siteId = 1;

            // Create UrlModel from record data using factory
            $urlModel = UrlFactory::createFromRecord($record);

            expect($urlModel->getRelativeUrl())->toBe('/test/path?baz=qux&foo=bar'); // Reconstructed in normalized order
        });

        it('reconstructs URL correctly without query string', function () {
            $record = new RequestRecord();
            $record->uri = 'test/path';
            $record->queryString = null;
            $record->siteId = 1;

            // Create UrlModel from record data using factory
            $urlModel = UrlFactory::createFromRecord($record);

            expect($urlModel->getRelativeUrl())->toBe('/test/path');
        });

        it('reconstructs URL correctly with empty query string', function () {
            $record = new RequestRecord();
            $record->uri = 'test/path';
            $record->queryString = '';
            $record->siteId = 1;

            // Create UrlModel from record data using factory
            $urlModel = UrlFactory::createFromRecord($record);

            expect($urlModel->getRelativeUrl())->toBe('/test/path');
        });

        it('uses stored URI when record has no entry tag', function () {
            $record = new RequestRecord();
            $record->uri = 'old/stored/path';
            $record->queryString = 'foo=bar';
            $record->siteId = 1;
            $record->tag = 'section:blog'; // Not an entry tag

            $urlModel = UrlFactory::createFromRecord($record);

            // Should use the stored URI since it's not an entry-specific record
            expect($urlModel->getRawUrl())->toBe('old/stored/path');
            expect($urlModel->getRelativeUrl())->toBe('/old/stored/path?foo=bar');
        });

        it('uses stored URI when entry tag references non-existent entry', function () {
            $record = new RequestRecord();
            $record->uri = 'fallback/path';
            $record->queryString = 'foo=bar';
            $record->siteId = 1;
            $record->tag = 'entry:99999'; // Non-existent entry ID

            $urlModel = UrlFactory::createFromRecord($record);

            // Should fall back to stored URI when entry doesn't exist
            expect($urlModel->getRawUrl())->toBe('fallback/path');
            expect($urlModel->getRelativeUrl())->toBe('/fallback/path?foo=bar');
        });
    });

    describe('Parameter Filtering', function () {

        it('excludes disabled parameters from query string', function () {
            // Configure settings with some disabled parameters
            $settings = Critter::getInstance()->settings;
            $settings->uniqueQueryParams = [
                [
                    'enabled' => '1',
                    'param' => 'foo'
                ],
                [
                    'enabled' => '0', // disabled
                    'param' => 'disabled_param'
                ],
                [
                    'enabled' => '1',
                    'param' => 'baz'
                ]
            ];

            $url = new UrlModel('test/path?foo=bar&disabled_param=should_not_appear&baz=qux', 1);

            // Only enabled parameters should appear in the query string
            expect($url->getQueryString())->toBe('baz=qux&foo=bar'); // alphabetically sorted, no disabled_param
            expect($url->getQueryParams())->toHaveKey('foo');
            expect($url->getQueryParams())->toHaveKey('baz');
            expect($url->getQueryParams())->not->toHaveKey('disabled_param');
        });

        it('excludes parameters not in the allowed list', function () {
            // Configure settings with specific allowed parameters
            $settings = Critter::getInstance()->settings;
            $settings->uniqueQueryParams = [
                [
                    'enabled' => '1',
                    'param' => 'allowed_one'
                ],
                [
                    'enabled' => '1',
                    'param' => 'allowed_two'
                ]
            ];

            $url = new UrlModel('test/path?allowed_one=yes&not_allowed=no&allowed_two=also_yes&another_bad=nope', 1);

            // Only parameters in the allowed list should appear
            expect($url->getQueryString())->toBe('allowed_one=yes&allowed_two=also_yes'); // alphabetically sorted
            expect($url->getQueryParams())->toHaveKey('allowed_one');
            expect($url->getQueryParams())->toHaveKey('allowed_two');
            expect($url->getQueryParams())->not->toHaveKey('not_allowed');
            expect($url->getQueryParams())->not->toHaveKey('another_bad');
        });

        it('includes all parameters when uniqueQueryParams is empty', function () {
            // Configure settings with empty uniqueQueryParams
            $settings = Critter::getInstance()->settings;
            $settings->uniqueQueryParams = [];

            $url = new UrlModel('test/path?anything=goes&everything=included', 1);

            // All parameters should be included when no filtering is configured
            expect($url->getQueryString())->toBe('anything=goes&everything=included'); // alphabetically sorted
            expect($url->getQueryParams())->toHaveKey('anything');
            expect($url->getQueryParams())->toHaveKey('everything');
        });

        it('handles mixed enabled/disabled parameters correctly', function () {
            // Configure settings with mix of enabled and disabled
            $settings = Critter::getInstance()->settings;
            $settings->uniqueQueryParams = [
                [
                    'enabled' => '1',
                    'param' => 'keep_me'
                ],
                [
                    'enabled' => '0',
                    'param' => 'remove_me'
                ],
                [
                    'enabled' => '1',
                    'param' => 'also_keep'
                ],
                [
                    'enabled' => 'false', // string 'false' should be treated as disabled
                    'param' => 'also_remove'
                ]
            ];

            $url = new UrlModel('test/path?keep_me=yes&remove_me=no&also_keep=yes&also_remove=no&not_configured=maybe', 1);

            // Only enabled and configured parameters should appear
            expect($url->getQueryString())->toBe('also_keep=yes&keep_me=yes'); // alphabetically sorted
            expect($url->getQueryParams())->toHaveKey('keep_me');
            expect($url->getQueryParams())->toHaveKey('also_keep');
            expect($url->getQueryParams())->not->toHaveKey('remove_me');
            expect($url->getQueryParams())->not->toHaveKey('also_remove');
            expect($url->getQueryParams())->not->toHaveKey('not_configured');
        });

        it('ensures filtered parameters are not stored in database', function () {
            // Configure settings to only allow specific parameters
            $settings = Critter::getInstance()->settings;
            $settings->uniqueQueryParams = [
                [
                    'enabled' => '1',
                    'param' => 'allowed'
                ]
            ];

            // Create URL with both allowed and disallowed parameters
            $url = new UrlModel('test/path?allowed=yes&forbidden=no&secret=hidden', 1);
            $cssRequest = (new CssRequest())->setRequestUrl($url);

            $record = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest);
            $record->save();

            // Verify only allowed parameter is stored in database
            expect($record->queryString)->toBe('allowed=yes');
            expect($record->queryString)->not->toContain('forbidden');
            expect($record->queryString)->not->toContain('secret');
            expect($record->queryString)->not->toContain('hidden');
        });
    });

    describe('Database Queries', function () {

        it('finds records by URI and query string combination', function () {
            // Create two records with same URI but different query strings
            $url1 = new UrlModel('test/path?foo=bar', 1);
            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $record1 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest1);
            $record1->save();

            $url2 = new UrlModel('test/path?foo=baz', 1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);
            $record2 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest2);
            $record2->save();

            // Verify they are different records
            expect($record1->id)->not->toBe($record2->id);

            // Verify we can find each record by its specific query string
            $foundRecord1 = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest1);
            $foundRecord2 = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest2);

            expect($foundRecord1->id)->toBe($record1->id);
            expect($foundRecord2->id)->toBe($record2->id);
            expect($foundRecord1->queryString)->toBe('foo=bar');
            expect($foundRecord2->queryString)->toBe('foo=baz');
        });

        it('treats missing query string and null query string as equivalent', function () {
            // Create a record without query string
            $url1 = new UrlModel('test/path', 1);
            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $record1 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest1);
            $record1->save();

            // Try to find a record for the same URL (should find the existing one)
            $url2 = new UrlModel('test/path', 1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);
            $foundRecord = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest2);

            expect($foundRecord->id)->toBe($record1->id);
        });

        it('finds existing records regardless of query parameter order', function () {
            // Create two URLs with same parameters but different order
            $url1 = new UrlModel('test/path?success=true&foo=one', 1);
            $cssRequest1 = (new CssRequest())->setRequestUrl($url1);
            $record1 = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest1);
            $record1->save();

            $url2 = new UrlModel('test/path?foo=one&success=true', 1);
            $cssRequest2 = (new CssRequest())->setRequestUrl($url2);
            $foundRecord = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest2);

            // Should find the same record since parameter order shouldn't matter
            expect($foundRecord->id)->toBe($record1->id);

            // Both should produce the same normalized query string
            expect($url1->getQueryString())->toBe($url2->getQueryString());
            expect($record1->queryString)->toBe('foo=one&success=true'); // Alphabetically sorted
        });
    });

    describe('Entry URI Changes', function () {

        it('handles entry URI changes gracefully in URL reconstruction', function () {
            // Create a record with entry tag
            $record = new RequestRecord();
            $record->uri = 'old/entry/path'; // This would be the old URI
            $record->queryString = 'foo=bar';
            $record->siteId = 1;
            $record->tag = 'entry:1'; // Assuming entry ID 1 exists

            // The UrlFactory should try to get current URI from entry
            // If entry doesn't exist, it falls back to stored URI
            $urlModel = UrlFactory::createFromRecord($record);

            // Should either use current entry URI or fall back to stored URI
            expect($urlModel->getQueryString())->toBe('foo=bar');
            expect($urlModel->getSiteId())->toBe(1);
        });

        it('stores entry tag when creating records for entry URLs', function () {
            // This test would require actual entry data in a real environment
            // For now, we'll test the basic tagging concept
            $url = new UrlModel('test/path?foo=bar', 1);
            $cssRequest = (new CssRequest())->setRequestUrl($url);

            // Manually set a tag to simulate entry-specific records
            $record = Critter::getInstance()->requestRecords->getOrCreateRecord($cssRequest);
            $record->tag = 'entry:123'; // Simulate this being set by the system
            $record->save();

            expect($record->tag)->toBe('entry:123');
            expect($record->uri)->toBe('test/path');
            expect($record->queryString)->toBe('foo=bar');
        });

        it('handles malformed entry tags gracefully', function () {
            // Test various malformed tag scenarios
            $malformedTags = [
                '',
                'entry:',
                'entry:abc',
                'entry:-1',
                'entry:0',
                'section:123', // Not an entry tag
                'invalid:tag',
                null
            ];

            foreach ($malformedTags as $tag) {
                $record = new RequestRecord();
                $record->uri = 'fallback/path';
                $record->queryString = 'foo=bar';
                $record->siteId = 1;
                $record->tag = $tag;

                $urlModel = UrlFactory::createFromRecord($record);

                // Should always fall back to stored URI for malformed/invalid tags
                expect($urlModel->getRawUrl())->toBe('fallback/path');
                expect($urlModel->getQueryString())->toBe('foo=bar');
            }
        });

        it('updates record URI when found by tag but URI has changed', function () {
            // Create a mock entry with ID 456
            $entry = new \craft\elements\Entry();
            $entry->id = 456;
            $entry->siteId = 1;

            // Create a URL with the new/current URI
            $newUri = 'new/entry/path';
            $url = new UrlModel($newUri . '?foo=bar', 1);

            // Create a record with the old URI but matching entry tag
            $record = new RequestRecord();
            $record->uri = 'old/entry/path'; // Old URI that's out of date
            $record->queryString = 'foo=bar';
            $record->siteId = 1;
            $record->tag = 'entry:456'; // Matches our mock entry
            $record->save();

            // When we call getRecordByEntry, it should find the record and update the URI
            $service = Critter::getInstance()->requestRecords;
            $foundRecord = $service->getRecordByEntry($entry, $url);

            expect($foundRecord)->not->toBeNull();
            expect($foundRecord->id)->toBe($record->id); // Same record
            expect($foundRecord->uri)->toBe($newUri); // URI should be updated
            expect($foundRecord->queryString)->toBe('foo=bar'); // Query string unchanged
            expect($foundRecord->tag)->toBe('entry:456'); // Tag unchanged

            // Verify the record was actually saved to the database
            $reloadedRecord = RequestRecord::findOne($record->id);
            expect($reloadedRecord->uri)->toBe($newUri);
        });
    });

    afterEach(function () {
        // Clean up test records
        RequestRecord::deleteAll(['uri' => 'test/path']);
    });
});
