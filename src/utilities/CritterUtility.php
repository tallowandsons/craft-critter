<?php

namespace mijewe\critter\utilities;

use Craft;
use craft\base\Utility;

/**
 * Critter Utility utility
 */
class CritterUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('critter', 'Critter');
    }

    static function id(): string
    {
        return 'critter';
    }

    public static function icon(): ?string
    {
        return 'wrench';
    }

    static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('critter/cp/utilities/critter/index');
    }
}
