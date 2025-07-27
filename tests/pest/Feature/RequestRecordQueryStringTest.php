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
            expect($record->queryString)->toBe('foo=bar&baz=qux');
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
    });

    describe('URL Reconstruction', function () {

        it('reconstructs full URL correctly with query string', function () {
            $record = new RequestRecord();
            $record->uri = 'test/path';
            $record->queryString = 'foo=bar&baz=qux';
            $record->siteId = 1;

            // Create UrlModel from record data using factory
            $urlModel = UrlFactory::createFromRecord($record);

            expect($urlModel->getRelativeUrl())->toBe('/test/path?foo=bar&baz=qux');
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
    });

    afterEach(function () {
        // Clean up test records
        RequestRecord::deleteAll(['uri' => 'test/path']);
    });
});
