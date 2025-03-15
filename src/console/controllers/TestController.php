<?php

namespace honchoagency\craftcriticalcssgenerator\console\controllers;

use Craft;
use craft\console\Controller;
use honchoagency\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use yii\console\ExitCode;

/**
 * Test controller
 */
class TestController extends Controller
{
    public $defaultAction = 'index';

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

    /**
     * critical-css-generator/test command
     */
    public function actionIndex(): int
    {
        // ...
        return ExitCode::OK;
    }

    public function actionGenerate()
    {
        $url = 'https://0336-86-9-86-160.ngrok-free.app/page-three';

        Craft::$app->queue->push(new GenerateCriticalCssJob([
            'url' => $url
        ]));
    }
}
