<?php

namespace honchoagency\craftcriticalcssgenerator\helpers;

use craft\web\Request;
use honchoagency\craftcriticalcssgenerator\Critical;

class UrlHelper
{

    static function getAllowedQueryParamsFromRequest(Request $request): array
    {
        $allowedQueryStringParams = Critical::getInstance()->settings->allowedQueryStringParams ?? [];

        $queryParams = $request->getQueryParams();

        // remove any query string params that are not allowed
        foreach ($queryParams as $key => $value) {
            if (!in_array($key, $allowedQueryStringParams)) {
                unset($queryParams[$key]);
            }
        }

        return $queryParams;
    }
}
