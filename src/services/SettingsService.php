<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use craft\models\Section_SiteSettings;
use honchoagency\craftcriticalcssgenerator\Critical;
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
    public function getConfigurableSections(int $siteId): array
    {
        $sections = Craft::$app->getEntries()->getAllSections() ?? [];

        // filter out sections that do not have urls
        $sections = array_filter($sections, function ($section) use ($siteId) {

            /** @var ?Section_SiteSettings */
            $siteSettings = $section->siteSettings[$siteId] ?? null;
            if ($siteSettings && $siteSettings->hasUrls) {
                return true;
            }
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
