<?php

namespace mijewe\craftcriticalcssgenerator\helpers;

use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\models\Settings;

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

    /**
     * Returns an array of all available cache behaviours as <select> options.
     */
    static function getCacheBehavioursAsSelectOptions(): array
    {
        return [
            [
                'label' => Critical::translate('Clear URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_CLEAR_URLS,
            ],
            [
                'label' => Critical::translate('Expire URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_EXPIRE_URLS,
            ],
            [
                'label' => Critical::translate('Refresh URLs'),
                'value' => Settings::CACHE_BEHAVIOUR_REFRESH_URLS,
            ],
        ];
    }
}
