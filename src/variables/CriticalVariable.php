<?php

namespace mijewe\craftcriticalcssgenerator\variables;

use Craft;
use mijewe\craftcriticalcssgenerator\Critical;

class CriticalVariable
{

    public function render()
    {

        $cssStr = Critical::getInstance()->css->getCssForRequest();

        // register inline css
        Craft::$app->getView()->registerCss($cssStr, Critical::getInstance()->settings->styleTagOptions);
    }
}
