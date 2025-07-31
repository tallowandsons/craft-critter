<?php

namespace tallowandsons\critter\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use tallowandsons\critter\Critter;
use yii\console\ExitCode;

/**
 * Css controller
 */
class CssController extends Controller
{
    public $defaultAction = 'index';

    /**
     * @var bool Expire all critical CSS
     */
    public bool $all = false;

    /**
     * @var int|null Entry ID to expire CSS for specific entry
     */
    public ?int $entry = null;

    /**
     * @var string|null Section handle to expire CSS for specific section
     */
    public ?string $section = null;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'expire':
                $options[] = 'all';
                $options[] = 'entry';
                $options[] = 'section';
                break;
            case 'regenerate':
                $options[] = 'all';
                $options[] = 'entry';
                $options[] = 'section';
                break;
            case 'generate-fallback':
                $options[] = 'entry';
                break;
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

    /**
     * Expire cached Critical CSS records by updating their expiry dates.
     * php craft critter/css/expire --all
     * php craft critter/css/expire --entry=123
     * php craft critter/css/expire --section=news
     */
    public function actionExpire()
    {
        $optionsCount = ($this->all ? 1 : 0) + ($this->entry ? 1 : 0) + ($this->section ? 1 : 0);

        if ($optionsCount === 0) {
            $this->printError("Error: You must specify either --all, --entry, or --section option.");
            $this->printInfo("Usage: php craft critter/css/expire --all");
            $this->printInfo("   or: php craft critter/css/expire --entry=123");
            $this->printInfo("   or: php craft critter/css/expire --section=news");
            return ExitCode::USAGE;
        }

        if ($optionsCount > 1) {
            $this->printError("Error: You can only specify one of --all, --entry, or --section options.");
            return ExitCode::USAGE;
        }

        if ($this->all) {
            return $this->expireAll();
        }

        if ($this->entry) {
            return $this->expireEntry($this->entry);
        }

        if ($this->section) {
            return $this->expireSection($this->section);
        }

        return ExitCode::USAGE;
    }

    private function expireAll(): int
    {
        $this->printInfo("Expiring all CSS records...");

        $response = Critter::getInstance()->utilityService->expireAll();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    private function expireEntry(int $entryId): int
    {
        $this->printInfo("Expiring CSS records for entry ID: {$entryId}...");

        $response = Critter::getInstance()->utilityService->expireEntry($entryId);

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    private function expireSection(string $sectionHandle): int
    {
        $this->printInfo("Expiring CSS records for section handle: {$sectionHandle}...");

        $response = Critter::getInstance()->utilityService->expireSection($sectionHandle);

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    /**
     * Regenerate Critical CSS records (expires and then regenerates)
     * php craft critter/css/regenerate --all
     * php craft critter/css/regenerate --entry=123
     * php craft critter/css/regenerate --section=news
     */
    public function actionRegenerate()
    {
        $optionsCount = ($this->all ? 1 : 0) + ($this->entry ? 1 : 0) + ($this->section ? 1 : 0);

        if ($optionsCount === 0) {
            $this->printError("Error: You must specify either --all, --entry, or --section option.");
            $this->printInfo("Usage: php craft critter/css/regenerate --all");
            $this->printInfo("   or: php craft critter/css/regenerate --entry=123");
            $this->printInfo("   or: php craft critter/css/regenerate --section=news");
            return ExitCode::USAGE;
        }

        if ($optionsCount > 1) {
            $this->printError("Error: You can only specify one of --all, --entry, or --section options.");
            return ExitCode::USAGE;
        }

        if ($this->all) {
            return $this->regenerateAll();
        }

        if ($this->entry) {
            return $this->regenerateEntry($this->entry);
        }

        if ($this->section) {
            return $this->regenerateSection($this->section);
        }

        return ExitCode::USAGE;
    }

    private function regenerateAll(): int
    {
        $this->printInfo("Regenerating all CSS records...");

        $response = Critter::getInstance()->utilityService->regenerateAll();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    private function regenerateEntry(int $entryId): int
    {
        $this->printInfo("Regenerating CSS records for entry ID: {$entryId}...");

        $response = Critter::getInstance()->utilityService->regenerateEntry($entryId);

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    private function regenerateSection(string $sectionHandle): int
    {
        $this->printInfo("Regenerating CSS records for section handle: {$sectionHandle}...");

        $response = Critter::getInstance()->utilityService->regenerateSection($sectionHandle);

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    /**
     * Regenerate all expired Critical CSS records
     * php craft critter/css/regenerate-expired
     */
    public function actionRegenerateExpired()
    {
        $this->printInfo("Regenerating expired CSS records...");

        $response = Critter::getInstance()->utilityService->regenerateExpired();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    /**
     * Clear stuck CSS generation records
     * php craft critter/css/clear-stuck
     */
    public function actionClearStuck()
    {
        $this->printInfo("Clearing stuck CSS records...");

        $response = Critter::getInstance()->utilityService->clearStuckRecords();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    /**
     * Clear Critter cache
     * php craft critter/css/clear-cache
     */
    public function actionClearCache()
    {
        $this->printInfo("Clearing Critter cache...");

        $response = Critter::getInstance()->utilityService->clearCache();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Generate fallback CSS from an entry
     * php craft critter/css/generate-fallback --entry=123
     */
    public function actionGenerateFallback()
    {
        if (!$this->entry) {
            $this->printError("Error: You must specify an entry ID using --entry option.");
            $this->printInfo("Usage: php craft critter/css/generate-fallback --entry=123");
            return ExitCode::USAGE;
        }

        $this->printInfo("Generating fallback CSS from entry ID: {$this->entry}...");

        $response = Critter::getInstance()->utilityService->generateFallbackCss($this->entry);

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    /**
     * Clear generated fallback CSS
     * php craft critter/css/clear-fallback
     */
    public function actionClearFallback()
    {
        $this->printInfo("Clearing generated fallback CSS...");

        $response = Critter::getInstance()->utilityService->clearGeneratedFallbackCss();

        if ($response->isSuccess()) {
            $this->printSuccess($response->getMessage());
        } else {
            $this->printError($response->getMessage());
        }

        return ExitCode::OK;
    }

    private function printSuccess(string $message, bool $newline = true): void
    {
        $this->stdout("✔ ", Console::FG_GREEN);
        $this->stdout($message . ($newline ? PHP_EOL : ''), Console::FG_GREEN);
    }

    private function printError(string $message, bool $newline = true): void
    {
        $this->stderr("✘ ", Console::FG_RED);
        $this->stderr($message . ($newline ? PHP_EOL : ''), Console::FG_RED);
    }

    private function printInfo(string $message, bool $newline = true): void
    {
        $this->stdout("ℹ︎ ", Console::FG_YELLOW);
        $this->stdout($message . ($newline ? PHP_EOL : ''), Console::FG_YELLOW);
    }
}
