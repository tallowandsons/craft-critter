<?php

/*
|--------------------------------------------------------------------------
| Craft Critter Plugin Test Configuration
|--------------------------------------------------------------------------
|
| Test configuration for the Critter Critical CSS plugin.
| These tests are designed to run from the main Craft project context
| with full access to the Craft environment.
|
*/

// Use the main project's Craft Pest TestCase for full Craft environment access
uses(
    markhuot\craftpest\test\TestCase::class,
    markhuot\craftpest\test\RefreshesDatabase::class,
)->in('./');

/*
|--------------------------------------------------------------------------
| Plugin Test Helpers
|--------------------------------------------------------------------------
|
| Helper functions specific to Critter plugin testing.
|
*/

function critterPlugin(): \tallowandsons\critter\Critter
{
    return \tallowandsons\critter\Critter::getInstance();
}

function getCritterSettings(): \tallowandsons\critter\models\Settings
{
    return critterPlugin()->getSettings();
}

/*
|--------------------------------------------------------------------------
| Test Environment Setup
|--------------------------------------------------------------------------
|
| Ensure the plugin is available and properly configured for testing.
|
*/

beforeAll(function () {
    // Verify the plugin is installed and available
    $plugin = \tallowandsons\critter\Critter::getInstance();
    if (!$plugin || !$plugin->isInstalled) {
        throw new Exception('Critter plugin is not installed or available for testing');
    }
});
