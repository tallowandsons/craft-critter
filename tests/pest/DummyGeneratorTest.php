<?php

use tallowandsons\critter\exceptions\RetryableCssGenerationException;
use tallowandsons\critter\generators\DummyGenerator;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\UrlModel;

describe('DummyGenerator Tests', function () {

    beforeEach(function () {
        $this->generator = new DummyGenerator();
        $this->urlModel = new UrlModel('https://example.com');
    });

    describe('Basic Functionality', function () {
        it('generates critical CSS successfully', function () {
            $response = $this->generator->generate($this->urlModel);

            expect($response->isSuccess())->toBeTrue();
            expect($response->getCss())->toBeInstanceOf(CssModel::class);
            expect($response->getCss()->getCss())->toContain('body { background-color: pink; }');
        });

        it('can be configured for failure', function () {
            $this->generator->success = false;

            $response = $this->generator->generate($this->urlModel);

            expect($response->isSuccess())->toBeFalse();
            expect($response->getException())->toBeInstanceOf(\Exception::class);
            expect($response->getException()->getMessage())->toBe('Dummy generator configured to fail');
        });

        it('throws RetryableCssGenerationException when configured', function () {
            $this->generator->exceptionType = 'RetryableCssGenerationException';

            $response = $this->generator->generate($this->urlModel);

            expect($response->isSuccess())->toBeFalse();
            expect($response->getException())->toBeInstanceOf(RetryableCssGenerationException::class);
            expect($response->getException()->getMessage())->toBe('Dummy generator throwing RetryableCssGenerationException for testing');
        });
    });

    describe('Settings', function () {
        it('has default settings', function () {
            expect($this->generator->success)->toBeTrue();
            expect($this->generator->exceptionType)->toBeNull();
            expect($this->generator->handle)->toBe('dummy');
        });

        it('has proper attribute labels', function () {
            $labels = $this->generator->attributeLabels();

            expect($labels)->toHaveKey('success');
            expect($labels)->toHaveKey('exceptionType');
            expect($labels['success'])->toBe('Success');
            expect($labels['exceptionType'])->toBe('Exception Type');
        });

        it('validates settings properly', function () {
            // Test valid settings
            $this->generator->success = true;
            $this->generator->exceptionType = 'RetryableCssGenerationException';
            expect($this->generator->validate())->toBeTrue();

            // Test empty exception type (should be valid)
            $this->generator->exceptionType = '';
            expect($this->generator->validate())->toBeTrue();

            // Test invalid exception type
            $this->generator->exceptionType = 'InvalidException';
            expect($this->generator->validate())->toBeFalse();
            expect($this->generator->hasErrors('exceptionType'))->toBeTrue();
        });
    });
});
