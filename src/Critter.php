<?php

namespace tallowandsons\critter;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use tallowandsons\critter\helpers\GeneratorHelper;
use tallowandsons\critter\models\Settings;
use tallowandsons\critter\services\CacheService;
use tallowandsons\critter\services\ConfigService;
use tallowandsons\critter\services\CssService;
use tallowandsons\critter\services\ExpirationService;
use tallowandsons\critter\services\GeneratorService;
use tallowandsons\critter\services\LogService;
use tallowandsons\critter\services\RequestRecordService;
use tallowandsons\critter\services\SettingsService;
use tallowandsons\critter\services\StorageService;
use tallowandsons\critter\services\UtilityService;
use tallowandsons\critter\utilities\CritterUtility;
use tallowandsons\critter\variables\CritterVariable;
use tallowandsons\critter\web\assets\cp\CpAsset;
use yii\base\Event;
use yii\base\View as BaseView;

/**
 * Critter plugin
 *
 * @method static Critter getInstance()
 * @method Settings getSettings()
 * @author tallow-and-sons <dev@honcho.agency>
 * @copyright tallow-and-sons
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read StorageService $storage
 * @property-read GeneratorService $generator
 * @property-read CssService $css
 * @property-read CacheService $cacheService
 * @property-read RequestRecordService $requestRecords
 * @property-read SettingsService $settingsService
 * @property-read ConfigService $configService
 * @property-read LogService $log
 * @property-read UtilityService $utilityService
 */
class Critter extends Plugin
{

    //  plugin information
    public const PLUGIN_NAME = 'Critter';
    public const PLUGIN_HANDLE = 'critter';

    // user permissions
    public const PERMISSION_MANAGE_CONFIG = 'critter:manageConfig';
    public const PERMISSION_MANAGE_CONFIG_VIEW = 'critter:manageConfig:view';
    public const PERMISSION_MANAGE_CONFIG_EDIT = 'critter:manageConfig:edit';

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'storage' => StorageService::class,
                'generator' => GeneratorService::class,
                'css' => CssService::class,
                'cache' => CacheService::class,
                'requestRecords' => RequestRecordService::class,
                'settingsService' => SettingsService::class,
                'configService' => ConfigService::class,
                'log' => LogService::class,
                'expiration' => ExpirationService::class,
                'utilityService' => UtilityService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerVariables();
        $this->registerAssetBundles();
        $this->registerClearCaches();
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

        // Entry save event handler for critical CSS expiration
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                // Only handle Entry elements
                if (!$event->element instanceof Entry) {
                    return;
                }

                /** @var Entry $entry */
                $entry = $event->element;

                // Get plugin settings
                $settings = Critter::getInstance()->getSettings();

                // Check if we should expire critical CSS on entry save
                if ($settings->onEntrySaveBehaviour === Settings::ENTRY_SAVE_EXPIRE_CSS) {
                    // Use expiration service to expire related CSS

                    // if the entry has no url, abort
                    if (!$entry->getUrl()) {
                        return;
                    }

                    Critter::getInstance()->expiration->expireCriticalCssForEntry($entry);
                }
            }
        );

        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITIES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = CritterUtility::class;
        });
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
                        self::PERMISSION_MANAGE_CONFIG_VIEW => [
                            'label' => self::translate('View plugin configuration'),
                            'nested' => [
                                self::PERMISSION_MANAGE_CONFIG_EDIT => [
                                    'label' => self::translate('Edit plugin configuration'),
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
     * Registers clear caches
     */
    private function registerClearCaches(): void
    {
        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key' => 'critter',
                    'label' => Critter::translate('Critter cache'),
                    'action' => [Critter::getInstance()->storage, 'clearAll'],
                ];
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
                        self::getPluginHandle() => self::getPluginHandle() . '/config/index',
                        self::getPluginHandle() . '/config' => self::getPluginHandle() . '/config/index',
                        self::getPluginHandle() . '/settings/general' => self::getPluginHandle() . '/settings/edit',
                        self::getPluginHandle() . '/settings/sections' => self::getPluginHandle() . '/settings/sections-edit',
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
        if ($user->checkPermission(self::PERMISSION_MANAGE_CONFIG_VIEW)) {
            $subNavs['config'] = [
                'label' => $this->translate('Configuration'),
                'url' => $this->cpUrl('config'),
            ];
        }

        // Only show link to Utility if user has permission
        if ($user->checkPermission(CritterUtility::class)) {
            $subNavs['utility'] = [
                'label' => Craft::t('app', 'Utilities'),
                'url' => UrlHelper::cpUrl('utilities/critter'),
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
     * returns whether the plugin is in developer mode
     * This is used to determine if the dummy generator should be registered
     * and if the developer mode settings should be shown in the CP.
     */
    public function isDeveloperMode(): bool
    {
        $craftDevMode = Craft::$app->getConfig()->getGeneral()->devMode;
        $pluginDevMode = $this->getSettings()->developerMode;

        return $craftDevMode && $pluginDevMode;
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

    /**
     * Logs an info message using the plugin's logger.
     */
    static function info(string $message, ?string $category = null): void
    {
        Critter::getInstance()->log->info($message, $category);
    }

    /**
     * Logs an error message using the plugin's logger.
     */
    static function error(string $message, ?string $category = null): void
    {
        Critter::getInstance()->log->error($message, $category);
    }

    /**
     * Logs a debug message using the plugin's logger.
     */
    static function debug(string $message, ?string $category = null): void
    {
        Critter::getInstance()->log->debug($message, $category);
    }

    /**
     * Logs a warning message using the plugin's logger.
     */
    static function warning(string $message, ?string $category = null): void
    {
        Critter::getInstance()->log->warning($message, $category);
    }
}
