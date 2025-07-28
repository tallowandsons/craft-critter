<?php

namespace mijewe\critter\factories;

use Craft;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\web\Request;
use mijewe\critter\helpers\UrlHelper as CritterUrlHelper;
use mijewe\critter\models\Tag;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;

class UrlFactory
{

    static function createFromRequest(?Request $request = null): UrlModel
    {
        if ($request === null) {
            $request = Craft::$app->getRequest();
        }

        $site = Craft::$app->getSites()->getCurrentSite();

        // $uri is the url without the base url or query string
        $uri = $request->getFullUri();

        // get query string
        $queryParams = CritterUrlHelper::getUniqueQueryParamsFromRequest($request);

        $urlModel = new UrlModel();
        $urlModel->siteId = $site->id;
        $urlModel->url = $uri;
        $urlModel->queryParams = $queryParams;

        return $urlModel;
    }

    static function createFromEntry(Entry $entry, int $siteId = null): UrlModel
    {
        $siteId = $siteId ?? $entry->siteId;

        $absoluteUrl = UrlHelper::siteUrl($entry->getUrl(), null, null, $siteId);

        $relativeUrl = UrlHelper::rootRelativeUrl($absoluteUrl);

        return new UrlModel($relativeUrl, $siteId);
    }

    /**
     * Create a UrlModel from a RequestRecord
     *
     * If the record has an entry tag, uses the entry's current URI instead of the stored URI
     * to handle cases where the entry's URI has changed since the record was created.
     *
     * @param RequestRecord $record
     * @return UrlModel
     */
    static function createFromRecord(RequestRecord $record): UrlModel
    {
        $uri = $record->uri; // Default to stored URI

        // If this record is for a specific entry, get the current URI from the entry
        $tag = Tag::fromString($record->tag);
        if ($tag->isEntry()) {
            $entry = $tag->getEntry($record->siteId);
            if ($entry) {
                // Use the entry's current URI instead of stored URI
                $uri = $entry->getUri() ?: '';
                // Remove leading slash if present to match stored format
                $uri = ltrim($uri, '/');
            }
        }

        $urlModel = new UrlModel($uri, $record->siteId);

        if ($record->queryString) {
            parse_str($record->queryString, $queryParams);
            $urlModel->setQueryParams($queryParams);
        }

        return $urlModel;
    }
}
