<?php

use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\UrlModel;
use craft\elements\Entry;

// These tests assume Craft's dependency injection and mocking is available

describe('UrlFactory', function () {
    it('creates UrlModel from Entry with correct siteId', function () {
        $entry = new class extends Entry {
            public function getUrl(): string
            {
                return 'about';
            }
        };
        $entry->siteId = 2;
        $urlModel = UrlFactory::createFromEntry($entry, 2);
        expect($urlModel)->toBeInstanceOf(UrlModel::class);
        expect($urlModel->getSiteId())->toBe(2);
        expect($urlModel->getPath())->toBe('about');
    });
});
