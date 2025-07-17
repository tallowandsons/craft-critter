<?php

namespace mijewe\critter\helpers;

use mijewe\critter\Critter;
use mijewe\critter\models\Settings;

class SettingsHelper
{

    /**
     * Returns an array of all available modes as <select> options.
     */
    static function getModesAsSelectOptions(): array
    {
        return [
            [
                'label' => 'Entry',
                'value' => Settings::MODE_ENTRY,
            ],
            [
                'label' => 'Section',
                'value' => Settings::MODE_SECTION,
            ]
        ];
    }

    /**
     * Returns an array of all available cache behaviours as <select> options.
     */
    static function getCacheBehavioursAsSelectOptions(): array
    {
        return [
            [
                'label' => Critter::translate('Clear URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_CLEAR_URLS,
            ],
            [
                'label' => Critter::translate('Expire URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_EXPIRE_URLS,
            ],
            [
                'label' => Critter::translate('Refresh URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_REFRESH_URLS,
            ],
        ];
    }
}
