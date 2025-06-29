<?php

namespace mijewe\craftcriticalcssgenerator\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\helpers\GeneratorHelper;
use mijewe\craftcriticalcssgenerator\helpers\SettingsHelper;
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
     * critical-css-generator/settings/edit action
     */
    public function actionEdit(): Response
    {

        $tabs = [
            'general' => [
                'label' => Critical::translate('General'),
                'url' => '#general',
            ],
            'storage' => [
                'label' => Critical::translate('Storage'),
                'url' => '#storage',
            ],
            'generator' => [
                'label' => Critical::translate('Generator'),
                'url' => '#generator',
            ],
        ];

        $blitzIsEnabled = Craft::$app->plugins->getPlugin('blitz') !== null;
        if ($blitzIsEnabled) {
            $tabs['cache'] = [
                'label' => Critical::translate('Cache'),
                'url' => '#cache',
            ];
        }

        return $this->renderTemplate('critical-css-generator/cp/settings/general', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'generatorTypeOptions' => GeneratorHelper::getGeneratorTypesAsSelectOptions(),
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'tabs' => $tabs,
            'blitzIsEnabled' => $blitzIsEnabled,
            'cacheBehaviourOptions' => SettingsHelper::getCacheBehavioursAsSelectOptions(),
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        // get the settings from the POST
        $postedSettings = Craft::$app->getRequest()->getBodyParam('settings', []);

        // apply them to the settings model
        $settings = $this->getSettings();
        $settings->setAttributes($postedSettings, false);

        // save the settings into the project config
        Craft::$app->getPlugins()->savePluginSettings(Critical::getInstance(), $settings->getAttributes());

        // let the user know the settings were saved
        $notice = Craft::t('critical-css-generator', 'Settings saved.');
        Craft::$app->getSession()->setSuccess($notice);

        // redirect to the settings page
        return $this->redirectToPostedUrl();
    }

    /**
     * critical-css-generator/settings/sections/edit action
     */
    public function actionSectionsEdit()
    {
        return $this->renderTemplate('critical-css-generator/cp/settings/sections', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'sections' => Critical::getInstance()->settingsService->getConfigurableSections(Cp::requestedSite()->id),
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
        return Critical::getInstance()->getSettings();
    }

    /**
     * The 'config' is the plugin settings from the config file.
     * This is used to determine whether the config file settings
     * are overriding the project config settings.
     * This function returns the config.
     */
    private function getConfig()
    {
        return Craft::$app->getConfig()->getConfigFromFile('critical-css-generator');
    }
}
