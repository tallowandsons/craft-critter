<?php

namespace mijewe\craftcriticalcssgenerator;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use mijewe\craftcriticalcssgenerator\models\Settings;
use mijewe\craftcriticalcssgenerator\services\CacheService;
use mijewe\craftcriticalcssgenerator\services\ConfigService;
use mijewe\craftcriticalcssgenerator\services\CssService;
use mijewe\craftcriticalcssgenerator\services\GeneratorService;
use mijewe\craftcriticalcssgenerator\services\SettingsService;
use mijewe\craftcriticalcssgenerator\services\StorageService;
use mijewe\craftcriticalcssgenerator\services\RequestRecordService;
use mijewe\craftcriticalcssgenerator\variables\CriticalVariable;
use mijewe\craftcriticalcssgenerator\helpers\GeneratorHelper;
use yii\base\Event;
use yii\base\View as BaseView;

/**
 * Critical CSS Generator plugin
 *
 * @method static Critical getInstance()
 * @method Settings getSettings()
 * @author mijewe <dev@honcho.agency>
 * @copyright mijewe
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read StorageService $storage
 * @property-read GeneratorService $generator
 * @property-read CssService $css
 * @property-read CacheService $cacheService
 * @property-read RequestRecordService $requestRecords
 * @property-read SettingsService $settingsService
 * @property-read ConfigService $configService
 */
class Critical extends Plugin
{

    //  plugin information
    public const PLUGIN_NAME = 'Critical CSS Generator';
    public const PLUGIN_HANDLE = 'critical-css-generator';

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => ['storage' => StorageService::class, 'generator' => GeneratorService::class, 'css' => CssService::class, 'cache' => CacheService::class, 'requestRecords' => RequestRecordService::class, 'settingsService' => SettingsService::class, 'configService' => ConfigService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerVariables();
        $this->attachEventHandlers();

        // register control panel URL rules
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->registerCpUrlRules();
        }

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function () {
            // ...
        });
    }

    /**
     * Returns the plugin name.
     */
    static function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * Returns the plugin handle.
     */
    static function getPluginHandle(): string
    {
        return self::PLUGIN_HANDLE;
    }

    /**
     * Returns the plugin URL segment.
     */
    static function getPluginUrlSegment(): string
    {
        return self::PLUGIN_HANDLE;
    }

    /**
     * Registers a custom generator class
     */
    public static function registerGenerator(string $generatorClass): void
    {
        GeneratorHelper::registerGenerator($generatorClass);
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('critical-css-generator/cp/settings', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        // Redirect to the custom settings page
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('critical-css-generator/settings/general'));
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)

        Event::on(
            View::class,
            BaseView::EVENT_END_PAGE,
            static function () {
                if (Critical::getInstance()->getSettings()->autoRenderEnabled) {
                    Critical::getInstance()->css->renderCss();
                }
            }
        );
    }

    /**
     * Registers variables
     */
    private function registerVariables(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('critical', CriticalVariable::class);
            }
        );
    }

    /**
     * Register control panel URL rules
     */
    private function registerCpUrlRules(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge(
                    [
                        'critical-css-generator' => 'critical-css-generator/config/sections-edit',
                        'critical-css-generator/settings/general' => 'critical-css-generator/settings/edit',
                        'critical-css-generator/settings/sections' => 'critical-css-generator/settings/sections-edit',
                        'critical-css-generator/sections' => 'critical-css-generator/config/sections-edit',
                    ],
                    $event->rules
                );
            }
        );
    }

    /**
     * build the control panel navigation for the plugin
     */
    public function getCpNavItem(): ?array
    {
        $nav = parent::getCpNavItem();

        $nav['subnav']['sections'] = [
            'label' => $this->translate('Sections'),
            'url' => $this->cpUrl('sections'),
        ];

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => $this->translate('Settings'),
                'url' => $this->cpUrl('settings/general'),
            ];
        }

        return $nav;
    }

    /**
     *  Generates a control panel URL for the plugin from a given path.
     */
    static function cpUrl(string $path): string
    {
        // strip trailing and leading slashes
        $path = trim($path, '/');

        return UrlHelper::cpUrl(Critical::getInstance()->getPluginUrlSegment() . '/' . $path);
    }

    /**
     * Translates a string using the plugin's translation context.
     */
    static function translate(string $str): string
    {
        return Craft::t(Critical::getInstance()->getPluginHandle(), $str);
    }
}
