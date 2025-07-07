<?php

namespace mijewe\critter\variables;

use Craft;
use mijewe\critter\Critter;

class CritterVariable
{

    public function render()
    {
        Critter::getInstance()->css->renderCss();
    }

    /**
     * Get the plugin handle.
     */
    public function pluginHandle()
    {
        return Critter::getPluginHandle();
    }

    /**
     * Get constants for use in templates.
     * Usage: craft.critter.const('PERMISSION_MANAGE_SECTIONS_EDIT')
     */
    public function const(string $constantName): string
    {
        $constantFullName = 'mijewe\\critter\\Critter::' . $constantName;

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
