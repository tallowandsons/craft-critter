<?php

namespace honchoagency\craftcriticalcssgenerator\helpers;

use craft\helpers\StringHelper;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\Settings;

class SettingsHelper
{

    /**
     * Returns an array of all available modes as <select> options.
     */
    static function getModesAsSelectOptions(): array
    {
        return [
            [
                'label' => 'URL',
                'value' => Settings::MODE_URL,
            ],
            [
                'label' => 'Section',
                'value' => Settings::MODE_SECTION,
            ]
        ];
    }
}
