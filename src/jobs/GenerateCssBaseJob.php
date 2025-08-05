<?php

namespace tallowandsons\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use tallowandsons\critter\Critter;
use tallowandsons\critter\exceptions\MutexLockException;
use tallowandsons\critter\exceptions\RetryableCssGenerationException;
use tallowandsons\critter\generators\NoGenerator;
use yii\queue\RetryableJobInterface;

/**
 * Base class for CSS generation jobs with retry logic
 */
abstract class GenerateCssBaseJob extends BaseJob implements RetryableJobInterface
{
    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return 500; // 5 minutes
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        $settings = Critter::getInstance()->getSettings();
        $context = $this->getJobContext();

        Critter::info(
            "Job retry check: {$context} - Attempt {$attempt}/{$settings->maxRetries}, Error: " . get_class($error) . " - " . $error->getMessage(),
            'generate'
        );

        // Only retry on specific retryable exceptions
        $retryableExceptions = [
            MutexLockException::class,
            RetryableCssGenerationException::class
        ];

        foreach ($retryableExceptions as $exceptionClass) {
            if ($error instanceof $exceptionClass) {
                $canRetry = $attempt < $settings->maxRetries;
                return $canRetry;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $context = $this->getJobContext();

        Craft::info(
            "Starting CSS generation job: {$context}",
            Critter::getPluginHandle()
        );

        // Early abort: Skip execution if NoGenerator is active
        if (NoGenerator::isActive()) {
            Craft::info(
                'Skipping job execution - NoGenerator is active (' . $context . ')',
                Critter::getPluginHandle()
            );
            return;
        }

        try {
            $this->performCssGeneration();

            Craft::info(
                "Successfully completed CSS generation job: {$context}",
                Critter::getPluginHandle()
            );
        } catch (\Throwable $e) {
            Craft::error(
                "CSS generation job failed: {$context} - Error: {$e->getMessage()}",
                Critter::getPluginHandle()
            );

            // Re-throw the exception so the queue system can handle retries
            throw $e;
        }
    }

    /**
     * Perform the actual CSS generation work
     * Subclasses must implement this method
     */
    abstract protected function performCssGeneration(): void;

    /**
     * Get context string for logging (e.g., URL or entry ID)
     * Subclasses must implement this method
     */
    abstract protected function getJobContext(): string;

    /**
     * Get the base description for this job type
     * Subclasses should implement this method
     */
    abstract protected function getJobDescription(): string;

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return $this->getJobDescription();
    }
}
