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
    public int $retryAttempt = 0;

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        $settings = Critter::getInstance()->getSettings();

        // Retry on specific retryable exceptions, up to the configured max retries
        $retryableExceptions = [
            MutexLockException::class,
            RetryableCssGenerationException::class
        ];

        foreach ($retryableExceptions as $exceptionClass) {
            if ($error instanceof $exceptionClass && $attempt <= $settings->maxRetries) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        // Allow longer time for jobs that might need to wait for mutex locks and API polling
        return 300; // 5 minutes
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // Early abort: Skip execution if NoGenerator is active
        if (NoGenerator::isActive()) {
            Craft::info(
                'Skipping job execution - NoGenerator is active (' . $this->getJobContext() . ')',
                Critter::getPluginHandle()
            );
            return;
        }

        try {
            $this->performCssGeneration();
        } catch (MutexLockException $e) {
            $this->handleRetryableException($e);
        } catch (RetryableCssGenerationException $e) {
            $this->handleRetryableException($e);
        }
    }

    /**
     * Handle retryable exceptions with delay mechanism
     */
    protected function handleRetryableException(\Throwable $e): void
    {
        // If this is a retry attempt, implement our own delay mechanism
        if ($this->retryAttempt > 0) {
            $settings = Critter::getInstance()->getSettings();
            $delay = $settings->retryBaseDelay * (2 ** ($this->retryAttempt - 1));

            $context = $this->getJobContext();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::info(
                "{$exceptionType} for {$context}, sleeping for {$delay}s before retry {$this->retryAttempt}",
                Critter::getPluginHandle()
            );

            sleep($delay);

            // Try again after delay
            $this->performCssGeneration();
        } else {
            // First attempt - queue a delayed retry manually to get exponential backoff
            $this->queueDelayedRetry($e);
            return; // Exit successfully so this job doesn't show as failed
        }
    }

    /**
     * Queue a delayed retry job with exponential backoff
     */
    protected function queueDelayedRetry(\Throwable $e): void
    {
        $settings = Critter::getInstance()->getSettings();
        $nextAttempt = $this->retryAttempt + 1;

        if ($nextAttempt <= $settings->maxRetries) {
            $delay = $settings->retryBaseDelay * (2 ** ($nextAttempt - 1));

            $context = $this->getJobContext();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::info(
                "{$exceptionType} for {$context}, queueing retry {$nextAttempt} with {$delay}s delay",
                Critter::getPluginHandle()
            );

            // Create retry job with incremented attempt counter
            $retryJob = $this->createRetryJob($nextAttempt);

            // Queue with delay
            Craft::$app->queue->delay($delay)->push($retryJob);
        } else {
            // Max retries exceeded
            $context = $this->getJobContext();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::error(
                "{$exceptionType} for {$context}, max retries ({$settings->maxRetries}) exceeded",
                Critter::getPluginHandle()
            );

            throw $e; // Let this job fail
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
     * Create a retry job instance with incremented attempt counter
     * Subclasses must implement this method
     */
    abstract protected function createRetryJob(int $nextAttempt): self;

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        $retryText = $this->retryAttempt > 0 ? " (retry {$this->retryAttempt})" : "";
        return $this->getJobDescription() . $retryText;
    }

    /**
     * Get the base description for this job type
     * Subclasses should implement this method
     */
    abstract protected function getJobDescription(): string;
}
