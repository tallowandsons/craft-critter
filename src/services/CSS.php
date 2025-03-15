<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use yii\base\Component;

/**
 * Css service
 */
class Css extends Component
{
    public $useQueue = true;
    public $fallbackCss = "/* fallback */ body { background-color: red; }";

    public function getCssForRequest(bool $generate = true)
    {
        $request = Craft::$app->getRequest();
        $absoluteUrl = $request->getAbsoluteUrl();

        if ($css = $this->getCssForUrl($absoluteUrl, $generate)) {
            return $css;
        }

        return $this->fallbackCss;
    }

    public function getCssForUrl(string $url, bool $generate = true)
    {
        // return css from storage if it exists
        if ($css = Critical::getInstance()->storage->get($url)) {
            return $css;
        }

        // generate new css
        if ($generate) {
            Critical::getInstance()->generator->generate($url, $this->useQueue);
        }

        return $this->fallbackCss;
    }
}
