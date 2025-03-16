<?php

namespace honchoagency\craftcriticalcssgenerator\console\controllers;

use Craft;
use craft\console\Controller;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\factories\UrlFactory;
use yii\console\ExitCode;

/**
 * Test controller
 */
class TestController extends Controller
{
    public $defaultAction = 'index';

    public string $testUrl = "https://criticalcssplugin.ddev.site/page-three?abc=123";

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
        $urlModel = UrlFactory::createFromUrl($this->testUrl);
        Critical::getInstance()->generator->startGenerate($urlModel, false, true);
    }

    public function actionGet()
    {
        $urlModel = UrlFactory::createFromUrl($this->testUrl);
        $css = Critical::getInstance()->css->getCssForUrl($urlModel);
        echo $css . PHP_EOL;
    }
}
