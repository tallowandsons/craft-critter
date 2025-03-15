<?php

namespace honchoagency\craftcriticalcssgenerator\factories;

use craft\helpers\UrlHelper;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class UrlFactory
{

    static function create(string $url): UrlModel
    {
        $url = UrlHelper::stripQueryString($url);
        return new UrlModel($url);
    }
}
