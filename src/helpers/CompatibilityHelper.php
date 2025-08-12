<?php

namespace tallowandsons\critter\helpers;

use Craft;
use craft\models\Section;
use craft\services\Utilities;

class CompatibilityHelper
{

    public static function getRegisterUtilitiesEvent(): string
    {
        if (version_compare(Craft::getVersion(), '5.0.0', '>=')) {
            return Utilities::EVENT_REGISTER_UTILITIES;
        }

        return Utilities::EVENT_REGISTER_UTILITY_TYPES;
    }

    public static function getSectionByHandle(string $handle): ?Section
    {
        $service = version_compare(Craft::getVersion(), '5.0.0', '>=') ? Craft::$app->entries : Craft::$app->sections;

        return $service->getSectionByHandle($handle);
    }

    public static function getAllSections(): array
    {
        $service = version_compare(Craft::getVersion(), '5.0.0', '>=') ? Craft::$app->entries : Craft::$app->sections;

        return $service->getAllSections();
    }
}
