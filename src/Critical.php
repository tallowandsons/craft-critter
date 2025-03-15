<?php

namespace honchoagency\craftcriticalcssgenerator;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use honchoagency\craftcriticalcssgenerator\models\Settings;
use honchoagency\craftcriticalcssgenerator\services\Css;
use honchoagency\craftcriticalcssgenerator\services\Generator;
use honchoagency\craftcriticalcssgenerator\services\QueueService;
use honchoagency\craftcriticalcssgenerator\services\StorageService;
use honchoagency\craftcriticalcssgenerator\variables\CriticalVariable;
use yii\base\Event;

/**
 * Critical CSS Generator plugin
 *
 * @method static Critical getInstance()
 * @method Settings getSettings()
 * @author honchoagency <dev@honcho.agency>
 * @copyright honchoagency
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read StorageService $storage
 * @property-read Generator $generator
 * @property-read Css $css
 * @property-read QueueService $queueService
 */
class Critical extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['storage' => StorageService::class, 'generator' => Generator::class, 'css' => Css::class, 'queueService' => QueueService::class],
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
