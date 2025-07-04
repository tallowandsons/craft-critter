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

    /**
     * Get constants for use in templates.
     * Usage: craft.critical.const('PERMISSION_MANAGE_SECTIONS_EDIT')
     */
    public function const(string $constantName): string
    {
        $constantFullName = 'mijewe\\craftcriticalcssgenerator\\Critical::' . $constantName;

        if (defined($constantFullName)) {
            return constant($constantFullName);
        }

        // Fallback: check if it's already a full constant name
        if (defined($constantName)) {
            return constant($constantName);
        }

        throw new \InvalidArgumentException("Constant '{$constantName}' not found in Critical class");
    }
}
