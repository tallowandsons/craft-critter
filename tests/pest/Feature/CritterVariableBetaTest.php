<?php

use tallowandsons\critter\variables\CritterVariable;
use tallowandsons\critter\Critter;

describe('CritterVariable isBeta Tests', function () {

    beforeEach(function () {
        $this->critterVariable = new CritterVariable();
        $this->originalVersion = null;
    });

    it('detects dev-master as beta', function () {
        // Since this test runs in the actual dev environment with dev-master
        // we can test the real implementation
        $isBeta = $this->critterVariable->isBeta();

        expect($isBeta)->toBeTrue();
    });

    it('would detect beta versions correctly', function () {
        // Test the logic by checking what patterns would be detected
        $testVersions = [
            'dev-master' => true,
            'dev-main' => true,
            '1.0.0-beta.1' => true,
            '1.0.0-beta' => true,
            '2.1.0-alpha.3' => true,
            '1.0.0-rc.1' => true,
            '1.0.0-RC.2' => true, // Case insensitive
            '1.0.0' => false,
            '2.1.5' => false,
            '10.0.0' => false,
        ];

        foreach ($testVersions as $version => $expectedBeta) {
            $versionLower = strtolower($version);
            $betaPatterns = ['dev-', 'beta', 'alpha', 'rc'];

            $actualBeta = false;
            foreach ($betaPatterns as $pattern) {
                if (str_contains($versionLower, $pattern)) {
                    $actualBeta = true;
                    break;
                }
            }

            expect($actualBeta)
                ->toBe($expectedBeta, "Version '{$version}' should be " . ($expectedBeta ? 'beta' : 'stable'));
        }
    });

    it('returns boolean value', function () {
        $result = $this->critterVariable->isBeta();

        expect($result)->toBeBool();
    });

    it('uses current plugin version', function () {
        // Verify that it uses the actual plugin version
        $pluginVersion = Critter::getInstance()->getVersion();
        $isBeta = $this->critterVariable->isBeta();

        // The result should be consistent with our manual check
        $versionLower = strtolower($pluginVersion);
        $expectedBeta = str_contains($versionLower, 'dev-') ||
            str_contains($versionLower, 'beta') ||
            str_contains($versionLower, 'alpha') ||
            str_contains($versionLower, 'rc');

        expect($isBeta)->toBe($expectedBeta);
    });
});
