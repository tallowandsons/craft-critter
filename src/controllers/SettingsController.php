<?php

namespace tallowandsons\critter\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use tallowandsons\critter\Critter;
use tallowandsons\critter\drivers\caches\BlitzCache;
use tallowandsons\critter\helpers\GeneratorHelper;
use tallowandsons\critter\helpers\SettingsHelper;
use tallowandsons\critter\helpers\CacheHelper;
use yii\web\Response;

/**
 * Settings controller
 */
class SettingsController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * @inerhitdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * critter/settings/edit action
     */
    public function actionEdit(): Response
    {

        $tabs = [
            'general' => [
                'label' => Critter::translate('General'),
                'url' => '#general',
            ],
            'generator' => [
                'label' => Critter::translate('Generator'),
                'url' => '#generator',
            ],
            'cache' => [
                'label' => Critter::translate('Cache'),
                'url' => '#cache',
            ],
        ];

        $blitzIsEnabled = Craft::$app->plugins->getPlugin('blitz') !== null;

        return $this->renderTemplate(Critter::getPluginHandle() . '/cp/settings/general', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'generators' => GeneratorHelper::getGeneratorInstances(),
            'generatorTypeOptions' => GeneratorHelper::getGeneratorTypesAsSelectOptions(),
            'caches' => CacheHelper::getCacheInstances(),
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'tabs' => $tabs,
            'blitzIsEnabled' => $blitzIsEnabled,
            'cacheTypeOptions' => CacheHelper::getCacheTypesAsSelectOptions(),
            'cacheBehaviourOptions' => BlitzCache::getCacheBehaviourOptions(),
            'defaultModeOptions' => SettingsHelper::getModesAsSelectOptions(),
            'entrySaveBehaviourOptions' => SettingsHelper::getEntrySaveBehaviourOptions(),
            'regenerateExpiredCssOptions' => SettingsHelper::getRegenerateExpiredCssOptions(),
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        // get the settings from the POST
        $postedSettings = $request->getBodyParam('settings', []);
        $generatorSettings = $request->getBodyParam('generatorSettings', []);
        $cacheSettings = $request->getBodyParam('cacheSettings', []);

        // Get the selected generator type to validate only that generator
        $selectedGeneratorType = $postedSettings['generatorType'] ?? null;

        // Validate only the currently selected generator
        if ($selectedGeneratorType && isset($generatorSettings[$selectedGeneratorType])) {
            if (class_exists($selectedGeneratorType)) {
                $generator = new $selectedGeneratorType();
                $generator->setAttributes($generatorSettings[$selectedGeneratorType], false);

                // Run validation
                $generator->validate();

                // Check for errors (security issues) - these BLOCK saving
                if ($generator->hasErrors()) {
                    $errors = [];
                    foreach ($generator->getErrors() as $attribute => $attributeErrors) {
                        foreach ($attributeErrors as $error) {
                            $errors[] = $generator->getAttributeLabel($attribute) . ': ' . $error;
                        }
                    }
                    Craft::$app->getSession()->setError(implode(' ', $errors));
                    return null;
                }

                // Check for warnings (functionality issues) - these DON'T block saving
                if ($generator->hasWarnings()) {
                    $warnings = [];
                    foreach ($generator->getWarnings() as $attribute => $attributeWarnings) {
                        foreach ($attributeWarnings as $warning) {
                            $warnings[] = $generator->getAttributeLabel($attribute) . ': ' . $warning;
                        }
                    }
                    Craft::$app->getSession()->setNotice('Settings saved with warnings: ' . implode(' ', $warnings));
                }
            }
        }

        // apply them to the settings model
        $settings = $this->getSettings();
        $settings->setAttributes($postedSettings, false);
        $settings->generatorSettings = $generatorSettings[$settings->generatorType] ?? [];
        $settings->cacheSettings = $cacheSettings[$settings->cacheType] ?? [];

        // save the settings into the project config
        Craft::$app->getPlugins()->savePluginSettings(Critter::getInstance(), $settings->getAttributes());

        // let the user know the settings were saved
        $notice = Critter::translate('Settings saved.');
        Craft::$app->getSession()->setSuccess($notice);

        // redirect to the settings page
        return $this->redirectToPostedUrl();
    }

    /**
     * critter/settings/sections/edit action
     */
    public function actionSectionsEdit()
    {
        return $this->renderTemplate(Critter::getPluginHandle() . '/cp/settings/sections', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'sections' => Critter::getInstance()->settingsService->getConfigurableSections(),
            'modeOptions' => SettingsHelper::getModesAsSelectOptions(),
        ]);
    }

    /**
     * The 'settings' are the current plugin settings from the Settings model,
     * which are a combination of the project config settings and the config file settings.
     * This functions returns the settings.
     */
    private function getSettings()
    {
        return Critter::getInstance()->getSettings();
    }

    /**
     * The 'config' is the plugin settings from the config file.
     * This is used to determine whether the config file settings
     * are overriding the project config settings.
     * This function returns the config.
     */
    private function getConfig()
    {
        return Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle());
    }
}
