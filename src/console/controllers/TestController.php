<?php

namespace honchoagency\craftcriticalcssgenerator\console\controllers;

use Craft;
use craft\console\Controller;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use yii\console\ExitCode;

/**
 * Test controller
 */
class TestController extends Controller
{
    public $defaultAction = 'index';

    public string $testUrl = "https://criticalcssplugin.ddev.site/page-three";

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
        $urlModel = new UrlModel($this->testUrl);
        Critical::getInstance()->generator->generate($urlModel, false, true);
    }

    public function actionGet()
    {
        $css = Critical::getInstance()->css->getCssForUrl($this->testUrl);
        echo $css . PHP_EOL;
    }
}
