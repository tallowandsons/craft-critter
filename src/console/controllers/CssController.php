<?php

namespace mijewe\critter\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use mijewe\critter\Critter;
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

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'expire':
                $options[] = 'all';
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
     */
    public function actionExpire()
    {
        $optionsCount = ($this->all ? 1 : 0) + ($this->entry ? 1 : 0);

        if ($optionsCount === 0) {
            $this->printError("Error: You must specify either --all or --entry option.");
            $this->printInfo("Usage: php craft critter/css/expire --all");
            $this->printInfo("   or: php craft critter/css/expire --entry=123");
            return ExitCode::USAGE;
        }

        if ($optionsCount > 1) {
            $this->printError("Error: You can only specify one of --all or --entry options.");
            return ExitCode::USAGE;
        }

        if ($this->all) {
            return $this->expireAll();
        }

        if ($this->entry) {
            return $this->expireEntry($this->entry);
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
