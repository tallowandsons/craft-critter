<?php

namespace mijewe\craftcriticalcssgenerator\helpers;

use craft\web\Request;
use mijewe\craftcriticalcssgenerator\Critical;

class UrlHelper
{

    static function getUniqueQueryParamsFromRequest(Request $request): array
    {
        $uniqueQueryParamsSettings = Critical::getInstance()->settings->uniqueQueryParams ?? [];

        // Extract enabled parameter names
        $enabledParams = [];
        foreach ($uniqueQueryParamsSettings as $setting) {
            $isEnabled = !empty($setting['enabled']) && filter_var($setting['enabled'], FILTER_VALIDATE_BOOLEAN);
            if ($isEnabled && !empty($setting['param'])) {
                $enabledParams[] = $setting['param'];
            }
        }

        $queryParams = $request->getQueryParams();

        // Keep only allowed query string params
        return array_intersect_key($queryParams, array_flip($enabledParams));
    }
}
