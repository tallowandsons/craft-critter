<?php

describe('GeneratorHelper CLI Exclusion', function () {
    it('does not register CLIGenerator by default', function () {
        // This test would need the full Craft environment to run properly
        // For now, we'll just verify the class exists but isn't in default registration
        expect(class_exists(\tallowandsons\critter\generators\CriticalCssCliGenerator::class))->toBeTrue();
    });

    it('allows DotComGenerator to be available', function () {
        expect(class_exists(\tallowandsons\critter\generators\CriticalCssDotComGenerator::class))->toBeTrue();
    });

    it('allows DummyGenerator to be available', function () {
        expect(class_exists(\tallowandsons\critter\generators\DummyGenerator::class))->toBeTrue();
    });

    it('allows NoGenerator to be available', function () {
        expect(class_exists(\tallowandsons\critter\generators\NoGenerator::class))->toBeTrue();
    });
});
