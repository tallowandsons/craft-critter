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
}
