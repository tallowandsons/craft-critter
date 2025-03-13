<?php

namespace honchoagency\craftcriticalcssgenerator\variables;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;

class CriticalVariable
{

    public function insert()
    {

        $cssStr = Critical::getInstance()->css->getCssForRequest();

        // register inline css
        Craft::$app->getView()->registerCss($cssStr, [
            'id' => 'js_critical-css-x',
        ]);
    }
}
