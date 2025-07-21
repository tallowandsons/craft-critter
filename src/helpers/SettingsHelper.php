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
     * Returns an array of entry save behaviour options as <select> options.
     */
    static function getEntrySaveBehaviourOptions(): array
    {
        return [
            [
                'label' => 'Do Nothing',
                'value' => Settings::ENTRY_SAVE_DO_NOTHING,
            ],
            [
                'label' => 'Expire Related Critical CSS',
                'value' => Settings::ENTRY_SAVE_EXPIRE_CSS,
            ]
        ];
    }
}
