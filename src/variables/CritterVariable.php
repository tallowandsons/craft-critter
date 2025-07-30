<?php

namespace tallowandsons\critter\variables;

use Craft;
use tallowandsons\critter\Critter;

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
     * Usage: craft.critter.const('CACHE_BEHAVIOUR_REFRESH_URLS', 'drivers\\caches\\BlitzCache')
     */
    public function const(string $constantName, string $namespace = 'Critter'): string
    {
        $fullNamespace = 'tallowandsons\\critter\\' . $namespace;
        $constantFullName = $fullNamespace . '::' . $constantName;

        if (defined($constantFullName)) {
            return constant($constantFullName);
        }

        // Fallback: check if it's already a full constant name
        if (defined($constantName)) {
            return constant($constantName);
        }

        throw new \InvalidArgumentException("Constant '{$constantName}' not found in {$fullNamespace} class");
    }

    public function isDeveloperMode(): bool
    {
        return Critter::getInstance()->isDeveloperMode();
    }
}
