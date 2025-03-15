<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\factories\UrlFactory;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
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
        $request = Craft::$app->getRequest();
        $absoluteUrl = $request->getAbsoluteUrl();

        return $this->getCssForUrl($absoluteUrl, $generate);
    }

    public function getCssForUrl(string $url, bool $generate = true)
    {
        // format url
        $url = UrlFactory::create($url);

        // return css from storage if it exists
        $cssModel = Critical::getInstance()->storage->get($url);

        if (!$cssModel->isEmpty()) {
            return $cssModel->getCss();
        }

        // generate new css
        if ($generate) {
            Critical::getInstance()->generator->generate($url, $this->useQueue);
        }

        return (new CssModel($this->fallbackCss))->getCss();
    }
}
