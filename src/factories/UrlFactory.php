<?php

namespace honchoagency\craftcriticalcssgenerator\factories;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Request;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class UrlFactory
{

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

    static function createFromRequest(?Request $request = null): UrlModel
    {
        if ($request === null) {
            $request = Craft::$app->getRequest();
        }

        $site = Craft::$app->getSites()->getCurrentSite();

        // $uri is the url without the base url or query string
        $uri = $request->getFullUri();

        return new UrlModel($uri, $site->id);
    }
}
