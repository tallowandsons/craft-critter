<?php

namespace mijewe\craftcriticalcssgenerator\console\controllers;

use Craft;
use craft\console\Controller;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\factories\UrlFactory;
use mijewe\craftcriticalcssgenerator\generators\CriticalCssDotComGenerator;
use yii\console\ExitCode;

/**
 * Test controller
 */
class TestController extends Controller
{
    public $defaultAction = 'index';

    public string $testUrl = "https://honcho.agency/about";

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

    public function actionApiGenerate()
    {
        $urlModel = UrlFactory::createFromUrl($this->testUrl);
        $generator = new CriticalCssDotComGenerator();
        $generator->generate($urlModel);
    }

    public function actionApiResults()
    {
        $id = "163673788XuXlhTN6B2";
        $generator = new CriticalCssDotComGenerator();
        $generator->getResults($id);
    }
}
