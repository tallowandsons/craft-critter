<?php

namespace mijewe\craftcriticalcssgenerator\variables;

use Craft;
use mijewe\craftcriticalcssgenerator\Critical;

class CriticalVariable
{

    public function render()
    {
        Critical::getInstance()->css->renderCss();
    }

    /**
     * Get the plugin handle.
     */
    public function pluginHandle()
    {
        return Critical::getPluginHandle();
    }
}
