<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use yii\base\Component;

/**
 * Css service
 */
class CSS extends Component
{

    public $fallbackCss = "/* fallback */ body { background-color: red; }";

    public function getCssForRequest()
    {
        $request = Craft::$app->getRequest();
        $path = $request->getPathInfo();

        if ($css = $this->getCssForPath($path)) {
            return $css;
        }


        // otherwise, generate new css and save to storage
        Critical::getInstance()->generator->generate($request, false);

        // get Entry
        // if ($entry = Craft::$app->getUrlManager()->getMatchedElement()) {
        //     $entryType = $entry->getType()->handle;
        //     $entryId = $entry->id;
        // }

        return $this->fallbackCss;
    }

    public function getCssForPath($path)
    {

        // return css from storage if it exists
        if ($css = Critical::getInstance()->storage->get($path)) {
            return $css;
        }

        return false;
    }
}
