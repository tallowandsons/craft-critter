<?php

namespace honchoagency\craftcriticalcssgenerator;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use honchoagency\craftcriticalcssgenerator\models\Settings;
use honchoagency\craftcriticalcssgenerator\services\CssService;
use honchoagency\craftcriticalcssgenerator\services\GeneratorService;
use honchoagency\craftcriticalcssgenerator\services\QueueService;
use honchoagency\craftcriticalcssgenerator\services\StorageService;
use honchoagency\craftcriticalcssgenerator\variables\CriticalVariable;
use yii\base\Event;
use yii\base\View as BaseView;

/**
 * Critical CSS Generator plugin
 *
 * @method static Critical getInstance()
 * @method Settings getSettings()
 * @author honchoagency <dev@honcho.agency>
 * @copyright honchoagency
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read StorageService $storage
 * @property-read GeneratorService $generator
 * @property-read CssService $css
 * @property-read QueueService $queueService
 */
class Critical extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['storage' => StorageService::class, 'generator' => GeneratorService::class, 'css' => CssService::class, 'queueService' => QueueService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerVariables();
        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function () {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('critical-css-generator/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)

        Event::on(
            View::class,
            BaseView::EVENT_END_PAGE,
            static function () {
                if (Craft::$app->getRequest()->getIsSiteRequest()) {
                    $autoRenderEnabled = Critical::getInstance()->getSettings()->autoRenderEnabled;
                    if ($autoRenderEnabled) {
                        Critical::getInstance()->css->renderCss();
                    }
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
}
