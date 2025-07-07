<?php

namespace mijewe\critter\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use mijewe\critter\Critter;
use mijewe\critter\helpers\GeneratorHelper;
use mijewe\critter\helpers\SettingsHelper;
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
        ];

        $blitzIsEnabled = Craft::$app->plugins->getPlugin('blitz') !== null;
        if ($blitzIsEnabled) {
            $tabs['cache'] = [
                'label' => Critter::translate('Cache'),
                'url' => '#cache',
            ];
        }

        return $this->renderTemplate(Critter::getPluginHandle() . '/cp/settings/general', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'generators' => GeneratorHelper::getGeneratorInstances(),
            'generatorTypeOptions' => GeneratorHelper::getGeneratorTypesAsSelectOptions(),
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'tabs' => $tabs,
            'blitzIsEnabled' => $blitzIsEnabled,
            'cacheBehaviourOptions' => SettingsHelper::getCacheBehavioursAsSelectOptions(),
            'defaultModeOptions' => SettingsHelper::getModesAsSelectOptions(),
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

        // apply them to the settings model
        $settings = $this->getSettings();
        $settings->setAttributes($postedSettings, false);
        $settings->generatorSettings = $generatorSettings[$settings->generatorType] ?? [];

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
