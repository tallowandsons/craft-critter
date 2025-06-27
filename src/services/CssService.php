<?php

namespace mijewe\craftcriticalcssgenerator\services;

use Craft;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\factories\UrlFactory;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\CssRequest;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
use yii\base\Component;

/**
 * Css service
 */
class CssService extends Component
{
    public $useQueue = true;
    public $fallbackCss = "/* fallback */ body { background-color: red; }";

    public function renderCss()
    {
        $css = $this->getCssForRequest();
        Craft::$app->getView()->registerCss($css, Critical::getInstance()->settings->styleTagOptions);
    }

    public function getCssForRequest(bool $generate = true)
    {
        $url = UrlFactory::createFromRequest(Craft::$app->getRequest());
        return $this->getCssForUrl($url, $generate);
    }

    public function getCssForUrl(UrlModel $url, bool $generate = true)
    {
        $cssRequest = (new CssRequest())->setRequestUrl($url);

        // create a record for the URL if it doesn't exist
        Critical::getInstance()->uriRecords->createRecordIfNotExists($cssRequest->getUrl());

        // return css from storage if it exists
        $cssModel = Critical::getInstance()->storage->get($cssRequest);
        if (!$cssModel->isEmpty()) {
            return $cssModel->getCss();
        }

        // if there is no css in storage, start generating new css and return fallback css
        if ($generate) {
            Critical::getInstance()->generator->startGenerate($cssRequest, $this->useQueue);
        }

        return (new CssModel($this->fallbackCss))->getCss();
    }
}
