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

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

    /**
     * Expire all cached Critical CSS records by updating their expiry dates.
     * php craft critter/css/expire
     */
    public function actionExpire()
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
