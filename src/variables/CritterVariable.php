<?php

namespace tallowandsons\critter\variables;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\Settings;
use tallowandsons\critter\services\ConfigService;
use tallowandsons\critter\services\FallbackService;

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

    public function settings(): Settings
    {
        return Critter::getInstance()->getSettings();
    }

    /**
     * Get the config service for template access
     */
    public function configService(): ConfigService
    {
        return Critter::getInstance()->configService;
    }

    /**
     * Get the fallback service for template access
     */
    public function fallbackService(): FallbackService
    {
        return Critter::getInstance()->fallbackService;
    }

    /**
     * Check if the plugin is in beta based on the version number
     * Returns true if version contains 'dev-', 'beta', 'alpha', or 'rc'
     */
    public function isBeta(): bool
    {
        $version = Critter::getInstance()->getVersion();

        // Check for common pre-release version patterns
        $betaPatterns = [
            'dev-',     // Development versions (e.g., dev-master)
            'beta',     // Beta versions (e.g., 1.0.0-beta.1)
            'alpha',    // Alpha versions (e.g., 1.0.0-alpha.1)
            'rc',       // Release candidates (e.g., 1.0.0-rc.1)
        ];

        $versionLower = strtolower($version);

        foreach ($betaPatterns as $pattern) {
            if (str_contains($versionLower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
