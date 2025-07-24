<?php

namespace mijewe\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use mijewe\critter\Critter;
use mijewe\critter\exceptions\MutexLockException;
use mijewe\critter\exceptions\RetryableCssGenerationException;
use mijewe\critter\generators\NoGenerator;
use mijewe\critter\models\CssRequest;
use yii\queue\RetryableJobInterface;

/**
 * Generate Critical Css Job queue job
 */
class GenerateCriticalCssJob extends BaseJob implements RetryableJobInterface
{

    public CssRequest $cssRequest;
    public bool $storeResult = true;
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
        return 10; // 10 seconds
    }
    function execute($queue): void
    {
        // Early abort: Skip execution if NoGenerator is active
        // This handles cases where jobs were queued before NoGenerator was configured
        if (NoGenerator::isActive()) {
            Craft::info(
                'Skipping job execution - NoGenerator is active (URL: ' . $this->cssRequest->getUrl()->getAbsoluteUrl() . ')',
                Critter::getPluginHandle()
            );
            return;
        }

        try {
            Critter::getInstance()->generator->generate($this->cssRequest, $this->storeResult);
        } catch (MutexLockException $e) {
            // Handle retryable exceptions with delay mechanism
            $this->handleRetryableException($e);
        } catch (RetryableCssGenerationException $e) {
            // Handle retryable CSS generation failures with delay mechanism
            $this->handleRetryableException($e);
        }
        // TODO: Add more catch blocks for other retryable exceptions as needed
        // catch (NetworkException $e) {
        //     $this->handleRetryableException($e);
        // }
    }

    /**
     * Handle retryable exceptions with delay mechanism
     */
    private function handleRetryableException(\Throwable $e): void
    {
        // If this is a retry attempt, implement our own delay mechanism
        if ($this->retryAttempt > 0) {
            $settings = Critter::getInstance()->getSettings();
            $delay = $settings->retryBaseDelay * (2 ** ($this->retryAttempt - 1));

            $url = $this->cssRequest->getUrl()->getAbsoluteUrl();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::info(
                "{$exceptionType} for {$url}, sleeping for {$delay}s before retry {$this->retryAttempt}",
                Critter::getPluginHandle()
            );

            sleep($delay);

            // Try again after delay
            Critter::getInstance()->generator->generate($this->cssRequest, $this->storeResult);
        } else {
            // First attempt - queue a delayed retry manually to get exponential backoff
            $this->queueDelayedRetry($e);
            return; // Exit successfully so this job doesn't show as failed
        }
    }
    /**
     * Queue a delayed retry job with exponential backoff
     */
    private function queueDelayedRetry(\Throwable $e): void
    {
        $settings = Critter::getInstance()->getSettings();
        $nextAttempt = $this->retryAttempt + 1;

        if ($nextAttempt <= $settings->maxRetries) {
            $delay = $settings->retryBaseDelay * (2 ** ($nextAttempt - 1));

            $url = $this->cssRequest->getUrl()->getAbsoluteUrl();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::info(
                "{$exceptionType} for {$url}, queueing retry {$nextAttempt} with {$delay}s delay",
                Critter::getPluginHandle()
            );

            // Create retry job with incremented attempt counter
            $retryJob = new self([
                'cssRequest' => $this->cssRequest,
                'storeResult' => $this->storeResult,
                'retryAttempt' => $nextAttempt
            ]);

            // Queue with delay
            Craft::$app->queue->delay($delay)->push($retryJob);
        } else {
            // Max retries exceeded
            $url = $this->cssRequest->getUrl()->getAbsoluteUrl();
            $exceptionType = (new \ReflectionClass($e))->getShortName();
            Craft::error(
                "{$exceptionType} for {$url}, max retries ({$settings->maxRetries}) exceeded",
                Critter::getPluginHandle()
            );

            throw $e; // Let this job fail
        }
    }

    protected function defaultDescription(): ?string
    {
        $url = $this->cssRequest->getUrl()->getAbsoluteUrl();
        $retryText = $this->retryAttempt > 0 ? " (retry {$this->retryAttempt})" : "";
        return 'Generating critical css for ' . $url . $retryText;
    }
}
