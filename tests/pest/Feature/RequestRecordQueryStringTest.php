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

    afterEach(function () {
        // Clean up test records
        RequestRecord::deleteAll(['uri' => 'test/path']);
    });
});
