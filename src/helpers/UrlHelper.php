<?php

namespace tallowandsons\critter\helpers;

use craft\web\Request;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\UrlModel;
use tallowandsons\critter\models\UrlPattern;

class UrlHelper
{

    static function getUniqueQueryParamsFromRequest(Request $request): array
    {
        $uniqueQueryParamsSettings = Critter::getInstance()->settings->uniqueQueryParams ?? [];

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

    /**
     * Check if a URL is excluded based on the excludePatterns setting
     */
    static function isExcludedUrl(UrlModel $url): bool
    {
        $settings = Critter::getInstance()->getSettings();
        foreach ($settings->excludePatterns as $patternArray) {

            $patternModel = UrlPattern::createFromArray($patternArray);

            // skip if not enabled or no pattern
            if (!$patternModel->isEnabled() || !$patternModel->hasPattern()) {
                continue;
            }

            // return if pattern matches, otherwise continue
            if ($patternModel->patternMatches($url)) {
                return true;
            }
        }
        return false;
    }
}
