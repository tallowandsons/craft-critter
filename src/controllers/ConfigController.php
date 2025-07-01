<?php

namespace mijewe\craftcriticalcssgenerator\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use mijewe\craftcriticalcssgenerator\Critical;
use yii\web\Response;

/**
 * Config controller
 */
class ConfigController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * critical-css-generator/settings/sections/edit action
     * loads the 'edit sections config' page.
     */
    public function actionSectionsEdit(): Response
    {
        $cpSite = Cp::requestedSite();

        $crumbs = [
            [
                'label' => Critical::getPluginName(),
                'url' => Critical::cpUrl('/')
            ],
            [
                'label' => Critical::translate('Sections'),
                'url' => Critical::cpUrl('/config/sections')
            ]
        ];

        return $this->renderTemplate('critical-css-generator/cp/config/sections', [
            'cpSite' => $cpSite,
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'sections' => Critical::getInstance()->settingsService->getConfigurableSections($cpSite->id),
            'sectionsConfig' => Critical::getInstance()->configService->getSectionConfigs(),
            'crumbs' => $this->formatCrumbs($crumbs),
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

    /**
     * Formats the breadcrumbs for the config pages.
     * Adds a language switcher crumb at the beginning.
     */
    private function formatCrumbs(array $crumbs)
    {
        $langSwitcherCrumb = [
            'id' => 'language-menu',
            'icon' => 'world',
            'label' => Craft::t('site', Cp::requestedSite()->name),
            'menu' => [
                'items' => Cp::siteMenuItems(),
                'label' => Craft::t('site', 'Select site'),
            ],
        ];

        return [$langSwitcherCrumb, ...$crumbs];
    }
}
