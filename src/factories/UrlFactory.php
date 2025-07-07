<?php

namespace mijewe\critter\factories;

use Craft;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\web\Request;
use mijewe\critter\helpers\UrlHelper as CritterUrlHelper;
use mijewe\critter\models\UrlModel;

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

    static function createFromUrl(string $url): UrlModel
    {
        // strip base url
        $url = UrlHelper::rootRelativeUrl($url);

        // strip query string
        $url = UrlHelper::stripQueryString($url);

        // trim slashes
        $url = trim($url, '/');

        return new UrlModel($url);
    }

    static function createFromEntry(Entry $entry): UrlModel
    {
        $url = UrlHelper::siteUrl($entry->getUrl(), null, null, $entry->siteId);
        return self::createFromUrl($url);
    }
}
