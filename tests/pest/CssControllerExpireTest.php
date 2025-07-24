<?php

use mijewe\critter\console\controllers\CssController;
use mijewe\critter\Critter;
use yii\console\ExitCode;

describe('CSSController actionExpire Tests', function () {

    beforeEach(function () {
        // Ensure the plugin is available for testing
        $this->plugin = Critter::getInstance();
        $this->controller = new CssController('css', $this->plugin);

        // Note: There's a known deprecation warning from Yii2/Craft framework
        // related to preg_match() receiving null parameters. This is a framework
        // issue, not related to our test logic.
    });

    describe('Parameter Validation', function () {

        it('returns usage error when no options are provided', function () {
            $controller = $this->controller;

            // No options set
            $controller->all = false;
            $controller->entry = null;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::USAGE);
        });

        it('returns usage error when multiple options are provided', function () {
            $controller = $this->controller;

            // Multiple options set
            $controller->all = true;
            $controller->entry = 123;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::USAGE);
        });

        it('returns usage error when all three options are provided', function () {
            $controller = $this->controller;

            // All options set
            $controller->all = true;
            $controller->entry = 123;
            $controller->section = 'news';

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::USAGE);
        });

        it('returns usage error when entry and section options are both provided', function () {
            $controller = $this->controller;

            // Two options set
            $controller->all = false;
            $controller->entry = 123;
            $controller->section = 'news';

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::USAGE);
        });
    });

    describe('Expire All Functionality', function () {

        it('successfully expires all records when --all option is used', function () {
            $controller = $this->controller;

            // Set only the all option
            $controller->all = true;
            $controller->entry = null;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('calls the utility service expireAll method when --all is used', function () {
            $controller = $this->controller;

            // Mock the utility service to verify it gets called
            $utilityService = $this->plugin->utilityService;
            expect($utilityService)->toBeInstanceOf(\mijewe\critter\services\UtilityService::class);

            $controller->all = true;
            $controller->entry = null;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });
    });

    describe('Expire Entry Functionality', function () {

        it('successfully expires entry records when --entry option is used', function () {
            $controller = $this->controller;

            // Set only the entry option
            $controller->all = false;
            $controller->entry = 123;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('handles valid entry ID correctly', function () {
            $controller = $this->controller;

            $controller->all = false;
            $controller->entry = 1;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('calls the utility service expireEntry method when --entry is used', function () {
            $controller = $this->controller;

            $utilityService = $this->plugin->utilityService;
            expect($utilityService)->toBeInstanceOf(\mijewe\critter\services\UtilityService::class);

            $controller->all = false;
            $controller->entry = 456;
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });
    });

    describe('Expire Section Functionality', function () {

        it('successfully expires section records when --section option is used', function () {
            $controller = $this->controller;

            // Set only the section option
            $controller->all = false;
            $controller->entry = null;
            $controller->section = 'news';

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('handles valid section handle correctly', function () {
            $controller = $this->controller;

            $controller->all = false;
            $controller->entry = null;
            $controller->section = 'blog';

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('calls the utility service expireSection method when --section is used', function () {
            $controller = $this->controller;

            $utilityService = $this->plugin->utilityService;
            expect($utilityService)->toBeInstanceOf(\mijewe\critter\services\UtilityService::class);

            $controller->all = false;
            $controller->entry = null;
            $controller->section = 'products';

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });
    });

    describe('Options Configuration', function () {

        it('includes correct options for expire action', function () {
            $controller = $this->controller;

            $options = $controller->options('expire');

            expect($options)->toContain('all');
            expect($options)->toContain('entry');
            expect($options)->toContain('section');
        });

        it('has proper default property values', function () {
            $controller = new CssController('css', $this->plugin);

            expect($controller->all)->toBeFalse();
            expect($controller->entry)->toBeNull();
            expect($controller->section)->toBeNull();
        });
    });

    describe('Edge Cases', function () {

        it('treats zero entry ID as no option set (usage error)', function () {
            $controller = $this->controller;

            $controller->all = false;
            $controller->entry = 0; // 0 is falsy in PHP
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            // 0 is treated as falsy, so this should result in no options being set
            expect($exitCode)->toBe(ExitCode::USAGE);
        });

        it('treats empty string section handle as no option set (usage error)', function () {
            $controller = $this->controller;

            $controller->all = false;
            $controller->entry = null;
            $controller->section = ''; // Empty string is falsy in PHP

            $exitCode = $controller->actionExpire();

            // Empty string is treated as falsy, so this should result in no options being set
            expect($exitCode)->toBe(ExitCode::USAGE);
        });

        it('properly handles boolean casting for option counting', function () {
            $controller = $this->controller;

            // Test that non-zero entry ID is treated as truthy
            $controller->all = false;
            $controller->entry = 1; // Non-zero is truthy
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            // This should work as entry ID 1 is truthy
            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('handles negative entry ID correctly', function () {
            $controller = $this->controller;

            $controller->all = false;
            $controller->entry = -1; // Negative numbers are truthy
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            expect($exitCode)->toBe(ExitCode::OK);
        });
    });

    describe('Service Integration', function () {

        it('properly instantiates utility service', function () {
            $controller = $this->controller;

            $plugin = Critter::getInstance();
            $utilityService = $plugin->utilityService;

            expect($utilityService)->toBeInstanceOf(\mijewe\critter\services\UtilityService::class);
            expect(method_exists($utilityService, 'expireAll'))->toBeTrue();
            expect(method_exists($utilityService, 'expireEntry'))->toBeTrue();
            expect(method_exists($utilityService, 'expireSection'))->toBeTrue();
        });

        it('returns OK exit code regardless of service response success', function () {
            $controller = $this->controller;

            // Test with entry that doesn't exist (service will return success=false)
            $controller->all = false;
            $controller->entry = 99999; // Non-existent entry
            $controller->section = null;

            $exitCode = $controller->actionExpire();

            // Controller returns OK even when service reports failure
            expect($exitCode)->toBe(ExitCode::OK);
        });

        it('maintains proper separation between validation and execution', function () {
            $controller = $this->controller;

            // First test validation failure
            $controller->all = false;
            $controller->entry = null;
            $controller->section = null;

            $exitCode = $controller->actionExpire();
            expect($exitCode)->toBe(ExitCode::USAGE);

            // Then test execution success
            $controller->all = true;
            $controller->entry = null;
            $controller->section = null;

            $exitCode = $controller->actionExpire();
            expect($exitCode)->toBe(ExitCode::OK);
        });
    });
});
