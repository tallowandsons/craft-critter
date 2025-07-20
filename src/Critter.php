<?php

namespace mijewe\critter;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use mijewe\critter\models\Settings;
use mijewe\critter\services\CacheService;
use mijewe\critter\services\ConfigService;
use mijewe\critter\services\CssService;
use mijewe\critter\services\GeneratorService;
use mijewe\critter\services\SettingsService;
use mijewe\critter\services\StorageService;
use mijewe\critter\services\RequestRecordService;
use mijewe\critter\variables\CritterVariable;
use mijewe\critter\helpers\GeneratorHelper;
use mijewe\critter\web\assets\cp\CpAsset;
use yii\base\Event;
use yii\base\View as BaseView;

/**
 * Critter plugin
 *
 * @method static Critter getInstance()
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
class Critter extends Plugin
{

    //  plugin information
    public const PLUGIN_NAME = 'Critter';
    public const PLUGIN_HANDLE = 'critter';

    // user permissions
    public const PERMISSION_MANAGE_SECTIONS = 'critter:manageSections';
    public const PERMISSION_MANAGE_SECTIONS_VIEW = 'critter:manageSections:view';
    public const PERMISSION_MANAGE_SECTIONS_EDIT = 'critter:manageSections:edit';

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
        $this->registerAssetBundles();
        $this->attachEventHandlers();
        $this->registerPermissions();

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
        return Craft::$app->view->renderTemplate(self::getPluginHandle() . '/cp/settings', [
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
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl(self::getPluginHandle() . '/settings/general'));
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)

        Event::on(
            View::class,
            BaseView::EVENT_END_PAGE,
            static function () {
                if (Critter::getInstance()->getSettings()->autoRenderEnabled) {
                    Critter::getInstance()->css->renderCss();
                }
            }
        );
    }

    /**
     * Register user permissions
     */
    private function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => self::getPluginName(),
                    'permissions' => [
                        self::PERMISSION_MANAGE_SECTIONS_VIEW => [
                            'label' => self::translate('View section configurations'),
                            'nested' => [
                                self::PERMISSION_MANAGE_SECTIONS_EDIT => [
                                    'label' => self::translate('Edit section configurations'),
                                ],
                            ]
                        ],
                    ]
                ];
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
                $variable->set('critter', CritterVariable::class);
            }
        );
    }

    /**
     * Registers asset bundles
     */
    private function registerAssetBundles(): void
    {
        // Load CSS before template is rendered
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function (TemplateEvent $event) {
                if (Craft::$app->getRequest()->getIsCpRequest()) {
                    Craft::$app->view->registerAssetBundle(CpAsset::class);
                }
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
                        self::getPluginHandle() => self::getPluginHandle() . '/config/sections-edit',
                        self::getPluginHandle() . '/settings/general' => self::getPluginHandle() . '/settings/edit',
                        self::getPluginHandle() . '/settings/sections' => self::getPluginHandle() . '/settings/sections-edit',
                        self::getPluginHandle() . '/sections' => self::getPluginHandle() . '/config/sections-edit',
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
        $user = Craft::$app->getUser();
        $nav = parent::getCpNavItem();
        $subNavs = [];

        // Only show sections subnav if user has view permissions
        if ($user->checkPermission(self::PERMISSION_MANAGE_SECTIONS_VIEW)) {
            $subNavs['sections'] = [
                'label' => $this->translate('Sections'),
                'url' => $this->cpUrl('sections'),
            ];
        }

        if ($user->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $subNavs['settings'] = [
                'label' => $this->translate('Settings'),
                'url' => $this->cpUrl('settings/general'),
            ];
        }

        // If no subnavs are available, don't show the plugin at all
        if (empty($subNavs)) {
            return null;
        }

        $nav['subnav'] = $subNavs;
        return $nav;
    }

    /**
     *  Generates a control panel URL for the plugin from a given path.
     */
    static function cpUrl(string $path): string
    {
        // strip trailing and leading slashes
        $path = trim($path, '/');

        return UrlHelper::cpUrl(Critter::getInstance()->getPluginUrlSegment() . '/' . $path);
    }

    /**
     * Translates a string using the plugin's translation context.
     */
    static function translate(string $str, array $params = []): string
    {
        return Craft::t(Critter::getInstance()->getPluginHandle(), $str, $params);
    }
}
