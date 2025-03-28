<?php

namespace honchoagency\craftcriticalcssgenerator\controllers;

use Craft;
use craft\web\Controller;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\helpers\GeneratorHelper;
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
        // $settings is the current plugin settings from the Settings model,
        // which are a combination of the project config settings and the config file settings.
        $settings = Critical::getInstance()->getSettings();

        // $config is the plugin settings from the config file.
        // this is used to determine whether the config file settings
        // are overriding the project config settings.
        $config = Craft::$app->getConfig()->getConfigFromFile('critical-css-generator');

        return $this->renderTemplate('critical-css-generator/cp/settings', [
            'settings' => $settings,
            'config' => $config,

            'generatorTypeOptions' => GeneratorHelper::getGeneratorTypesAsSelectOptions()
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $postedSettings = $request->getBodyParam('settings', []);

        $settings = Critical::getInstance()->settings;
        $settings->setAttributes($postedSettings, false);

        Craft::$app->getPlugins()->savePluginSettings(Critical::getInstance(), $settings->getAttributes());

        $notice = Craft::t('critical-css-generator', 'Settings saved.');

        Craft::$app->getSession()->setSuccess($notice);

        return $this->redirectToPostedUrl();
    }
}
