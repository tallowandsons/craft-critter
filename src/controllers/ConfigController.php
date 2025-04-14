<?php

namespace honchoagency\craftcriticalcssgenerator\controllers;

use Craft;
use craft\web\Controller;
use honchoagency\craftcriticalcssgenerator\Critical;
use yii\web\Response;

/**
 * Config controller
 */
class ConfigController extends Controller
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
     * critical-css-generator/settings/sections/edit action
     * loads the 'edit sections config' page.
     */
    public function actionSectionsEdit(): Response
    {
        return $this->renderTemplate('critical-css-generator/cp/config/sections', [
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'sections' => Critical::getInstance()->settingsService->getConfigurableSections(),
            'sectionsConfig' => Critical::getInstance()->configService->getSectionsConfig()
        ]);
    }

    /**
     * critical-css-generator/settings/sections/save action
     * saves all config to the database
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        // get the settings from the POST
        $postedSettings = Craft::$app->getRequest()->getBodyParam('config', []);

        if (!Critical::getInstance()->configService->save($postedSettings)) {
            Craft::$app->getSession()->setError(Craft::t('critical-css-generator', 'Unable to save settings.'));
            return null;
        }

        // set a success message
        Craft::$app->getSession()->setNotice(Craft::t('critical-css-generator', 'Settings saved.'));
        return $this->redirectToPostedUrl();
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
