<?php

describe('Settings generators array', function () {
    it('has generators property that defaults to null', function () {
        $settings = new \tallowandsons\critter\models\Settings();
        expect($settings->generators)->toBeNull();
    });

    it('allows setting custom generators array', function () {
        $settings = new \tallowandsons\critter\models\Settings();
        $settings->generators = [
            \tallowandsons\critter\generators\NoGenerator::class,
            \tallowandsons\critter\generators\CriticalCssDotComGenerator::class,
        ];

        expect($settings->generators)->toBeArray();
        expect($settings->generators)->toHaveCount(2);
        expect($settings->generators[0])->toBe(\tallowandsons\critter\generators\NoGenerator::class);
        expect($settings->generators[1])->toBe(\tallowandsons\critter\generators\CriticalCssDotComGenerator::class);
    });
});
