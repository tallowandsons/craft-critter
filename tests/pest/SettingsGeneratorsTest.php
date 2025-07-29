<?php

describe('Settings generators array', function () {
    it('has generators property that defaults to null', function () {
        $settings = new \mijewe\critter\models\Settings();
        expect($settings->generators)->toBeNull();
    });

    it('allows setting custom generators array', function () {
        $settings = new \mijewe\critter\models\Settings();
        $settings->generators = [
            \mijewe\critter\generators\NoGenerator::class,
            \mijewe\critter\generators\CriticalCssDotComGenerator::class,
        ];

        expect($settings->generators)->toBeArray();
        expect($settings->generators)->toHaveCount(2);
        expect($settings->generators[0])->toBe(\mijewe\critter\generators\NoGenerator::class);
        expect($settings->generators[1])->toBe(\mijewe\critter\generators\CriticalCssDotComGenerator::class);
    });
});
