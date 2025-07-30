<?php

namespace tallowandsons\critter\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use tallowandsons\critter\Critter;
use yii\web\Response;

/**
 * Config controller
 */
class ConfigController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Check for sections management permission
        $this->requirePermission(Critter::PERMISSION_MANAGE_SECTIONS_VIEW);

        return parent::beforeAction($action);
    }

    /**
     * critter/settings/sections/edit action
     * loads the 'edit sections config' page.
     */
    public function actionSectionsEdit(): Response
    {
        $cpSite = Cp::requestedSite();

        if (!$cpSite) {
            throw new \yii\web\BadRequestHttpException('No site specified.');
        }

        $crumbs = [
            [
                'label' => Critter::getPluginName(),
                'url' => Critter::cpUrl('/')
            ],
            [
                'label' => Critter::translate('Sections'),
                'url' => Critter::cpUrl('/config/sections')
            ]
        ];

        return $this->renderTemplate(Critter::getPluginHandle() . '/cp/config/sections', [
            'cpSite' => $cpSite,
            'settings' => $this->getSettings(),
            'config' => $this->getConfig(),
            'sections' => Critter::getInstance()->settingsService->getConfigurableSections($cpSite->id),
            'sectionsConfig' => Critter::getInstance()->configService->getSectionConfigs(),
            'crumbs' => $this->formatCrumbs($crumbs),
            'pluginHandle' => Critter::getPluginHandle(),
        ]);
    }

    /**
     * critter/settings/sections/save action
     * saves all config to the database
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        // Check for edit permission
        $this->requirePermission(Critter::PERMISSION_MANAGE_SECTIONS_EDIT);

        // get the settings from the POST
        $postedSettings = Craft::$app->getRequest()->getBodyParam('config', []);

        if (!Critter::getInstance()->configService->save($postedSettings)) {
            Craft::$app->getSession()->setError(Critter::translate('Unable to save settings.'));
            return null;
        }

        // set a success message
        Craft::$app->getSession()->setNotice(Critter::translate('Settings saved.'));
        return $this->redirectToPostedUrl();
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
