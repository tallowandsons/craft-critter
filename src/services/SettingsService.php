<?php

namespace mijewe\craftcriticalcssgenerator\services;

use Craft;
use craft\models\Section_SiteSettings;
use mijewe\craftcriticalcssgenerator\Critical;
use yii\base\Component;

/**
 * Settings Service service
 */
class SettingsService extends Component
{

    /**
     * Get all sections that can be configured by the plugin for a given site.
     * (i.e. sections that have urls)
     */
    public function getConfigurableSections(?int $siteId = null): array
    {
        $sections = Craft::$app->getEntries()->getAllSections() ?? [];

        $sections = array_filter($sections, function ($section) use ($siteId) {
            // If no siteId is provided, check if any siteSettings have URLs enabled
            if ($siteId === null) {
                foreach ($section->siteSettings as $settings) {
                    if ($settings->hasUrls) {
                        return true;
                    }
                }
                return false;
            }

            /** @var ?Section_SiteSettings */
            $siteSettings = $section->siteSettings[$siteId] ?? null;
            return $siteSettings && $siteSettings->hasUrls;
        });

        return $sections;
    }

    /**
     * Each section has a 'mode', which determines whether to
     * generate unique critical css for each url, or once
     * for the entire section.
     * This function returns the mode for a given section, as
     * configured in the settings.
     */
    public function getSectionMode(string $handle): ?string
    {
        $settings = Critical::getInstance()->settings->sectionSettings[$handle] ?? null;

        if (!$settings) {
            return null;
        }

        if (!isset($settings['mode'])) {
            return null;
        }

        return $settings['mode'];
    }
}
