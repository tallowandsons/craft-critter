<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\factories\UrlFactory;
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

        return $this->getCssForUrl($absoluteUrl, $generate);
    }

    public function getCssForUrl(string $url, bool $generate = true)
    {
        // format url
        $url = UrlFactory::create($url);

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
