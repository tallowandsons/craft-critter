<?php

namespace tallowandsons\critter\drivers\caches;

use Craft;
use craft\base\Model;
use craft\web\twig\TemplateLoaderException;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\UrlModel;

class BaseCache extends Model implements CacheInterface
{

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel|array $urlModels): void {}

    /**
     * Returns the settings HTML for this cache type
     */
    public function getSettingsHtml(): ?string
    {
        $settings = array_merge(
            [
                'cache' => $this,
                'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ],
            $this->getSettings()
        );

        $templatePath = sprintf(
            '%s/cp/settings/includes/caches/%s/settings',
            Critter::getPluginHandle(),
            $this->handle ?? 'base'
        );

        try {
            return Craft::$app->getView()->renderTemplate($templatePath, $settings);
        } catch (TemplateLoaderException $e) {
            // Template file doesn't exist, return null
            return null;
        }
    }

    /**
     * Return the settings for this cache.
     * This is used to display the settings in the CP.
     */
    public function getSettings(): array
    {
        return [];
    }

    /**
     * Returns a warning message if the cache is unavailable.
     * This is used to display a warning in the CP.
     */
    public function getUnavailableWarning(): ?string
    {
        return null;
    }
}
