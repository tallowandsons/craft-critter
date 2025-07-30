<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\base\Component;
use craft\log\MonologTarget;
use tallowandsons\critter\Critter;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * Log service
 *
 * Provides dedicated logging functionality for the Critter plugin,
 * using MonologTarget following Craft standards similar to Blitz.
 */
class LogService extends Component
{
    /**
     * @var bool Whether to enable debug logging
     */
    public bool $enableDebugLogging = false;

    /**
     * @var string The log channel name
     */
    private string $_logChannel = 'critter';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Get debug logging setting from plugin settings
        $settings = Critter::getInstance()->getSettings();
        $this->enableDebugLogging = $settings->enableDebugLogging ?? $this->enableDebugLogging;

        // Set up dedicated MonologTarget for Critter logs
        $this->_setupMonologTarget();
    }

    /**
     * Log an informational message
     */
    public function info(string $message, string $category = null): void
    {
        $this->log($message, LogLevel::INFO, $category);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, string $category = null): void
    {
        $this->log($message, LogLevel::WARNING, $category);
    }

    /**
     * Log an error message
     */
    public function error(string $message, string $category = null): void
    {
        $this->log($message, LogLevel::ERROR, $category);
    }

    /**
     * Log a debug message (only if debug logging is enabled)
     */
    public function debug(string $message, string $category = null): void
    {
        if ($this->enableDebugLogging) {
            $this->log($message, LogLevel::DEBUG, $category);
        }
    }

    /**
     * Log a critical error message
     */
    public function critical(string $message, string $category = null): void
    {
        $this->log($message, LogLevel::CRITICAL, $category);
    }

    /**
     * Log a message with the specified level
     */
    public function log(string $message, string $level = LogLevel::INFO, string $category = null): void
    {
        // Default category to the plugin handle
        if ($category === null) {
            $category = Critter::getPluginHandle();
        } else {
            // Prefix category with plugin handle for namespacing
            $category = Critter::getPluginHandle() . '.' . $category;
        }

        // Convert PSR log level to Yii log level
        $yiiLevel = $this->_convertLogLevel($level);

        // Log the message using Craft's logger
        Craft::getLogger()->log($message, $yiiLevel, $category);
    }

    /**
     * Log critical CSS generation start
     */
    public function logGenerationStart(string $url, string $generator): void
    {
        $this->info("Starting critical CSS generation for '{$url}' using {$generator}", 'generation');
    }

    /**
     * Log critical CSS generation completion
     */
    public function logGenerationComplete(string $url, string $generator, float $duration = null): void
    {
        $durationText = $duration ? " in {$duration}s" : '';
        $this->info("Completed critical CSS generation for '{$url}' using {$generator}{$durationText}", 'generation');
    }

    /**
     * Log critical CSS generation failure
     */
    public function logGenerationFailure(string $url, string $generator, string $error): void
    {
        $this->error("Failed to generate critical CSS for '{$url}' using {$generator}: {$error}", 'generation');
    }

    /**
     * Log cache operation
     */
    public function logCacheOperation(string $operation, string $url, string $cacheType): void
    {
        $this->info("Cache operation '{$operation}' for '{$url}' using {$cacheType}", 'cache');
    }

    /**
     * Log storage operation
     */
    public function logStorageOperation(string $operation, string $url, string $storageType): void
    {
        $this->debug("Storage operation '{$operation}' for '{$url}' using {$storageType}", 'storage');
    }

    /**
     * Log queue job operation
     */
    public function logQueueJob(string $operation, string $url, int $jobId = null): void
    {
        $jobText = $jobId ? " (Job #{$jobId})" : '';
        $this->info("Queue job '{$operation}' for '{$url}'{$jobText}", 'queue');
    }

    /**
     * Set up the MonologTarget for Critter logs
     */
    private function _setupMonologTarget(): void
    {
        // Only setup if we have a valid dispatcher
        if (!(Craft::getLogger()->dispatcher instanceof Dispatcher)) {
            return;
        }

        // Determine the log level based on debug setting
        $logLevel = $this->enableDebugLogging ? LogLevel::DEBUG : LogLevel::INFO;

        // Create the MonologTarget with Critter-specific configuration
        $target = new MonologTarget([
            'name' => $this->_logChannel,
            'categories' => [Critter::getPluginHandle() . '*'],
            'level' => $logLevel,
            'logContext' => false,
            'allowLineBreaks' => false,
            'maxFiles' => 5,
            'formatter' => new LineFormatter(
                format: "[%datetime%] [%level_name%] [%extra.yii_category%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
                allowInlineLineBreaks: false,
                ignoreEmptyContextAndExtra: true,
            ),
        ]);

        // Add the target to the logger dispatcher
        Craft::getLogger()->dispatcher->targets[$this->_logChannel] = $target;
    }

    /**
     * Convert PSR log level to Yii log level
     */
    private function _convertLogLevel(string $psrLevel): int
    {
        return match ($psrLevel) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => Logger::LEVEL_ERROR,
            LogLevel::ERROR => Logger::LEVEL_ERROR,
            LogLevel::WARNING => Logger::LEVEL_WARNING,
            LogLevel::NOTICE, LogLevel::INFO => Logger::LEVEL_INFO,
            LogLevel::DEBUG => Logger::LEVEL_TRACE,
            default => Logger::LEVEL_INFO,
        };
    }
}
