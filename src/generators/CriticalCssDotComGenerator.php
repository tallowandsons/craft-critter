<?php

namespace tallowandsons\critter\generators;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use tallowandsons\critter\Critter;
use tallowandsons\critter\drivers\apis\CriticalCssDotComApi;
use tallowandsons\critter\exceptions\MutexLockException;
use tallowandsons\critter\exceptions\RetryableCssGenerationException;
use tallowandsons\critter\models\api\CriticalCssDotComResultsResponse;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\models\GeneratorResponse;
use tallowandsons\critter\models\UrlModel;

class CriticalCssDotComGenerator extends BaseGenerator
{
    public string $handle = 'criticalcssdotcom';

    /**
     * Cache key for global job tracking
     */
    private const GLOBAL_JOB_CACHE_KEY = 'critter:global-generate-job';

    /**
     * Cache TTL for global job tracking (5 minutes)
     */
    private const GLOBAL_JOB_CACHE_TTL = 300;

    /**
     * @var string API key for the criticalcss.com account
     */
    public ?string $apiKey = null;

    /**
     * @var int The maximum number of times to poll the API for the results
     */
    public int $maxAttempts = 10;

    /**
     * @var int The number of seconds to wait between each poll attempt
     */
    public int $attemptDelay = 4;

    /**
     * @var int Viewport width for critical CSS generation
     */
    public int $width = CriticalCssDotComApi::DEFAULT_WIDTH;

    /**
     * @var int Viewport height for critical CSS generation
     */
    public int $height = CriticalCssDotComApi::DEFAULT_HEIGHT;

    /**
     * @var bool Enable test mode for simulating API responses
     */
    public bool $testMode = false;

    /**
     * @var string The result status to simulate in test mode
     * Options: 'PENTHOUSE_TIMEOUT'
     */
    public ?string $testResultStatus = null;

    /**
     * @var CriticalCssDotComApi Internal API client (not a model attribute)
     */
    private ?CriticalCssDotComApi $api = null;

    public function __construct()
    {
        $generatorSettings = Critter::getInstance()->settings->generatorSettings ?? [];

        // Load settings from configuration using setAttributes
        $this->setAttributes($generatorSettings, false);

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Critter::translate('criticalcss.com Generator');
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        // Check if API key is configured
        if (!$this->getParsedApiKey()) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException(new \Exception(
                    Critter::translate('criticalcss.com API key is not configured')
                ));
        }

        // the criticalcss.com API works like this:
        // 1.   trigger a generate job by POSTing to the API.
        //      This will return a job number, while the CSS is being
        //      generated on the criticalcss.com servers.
        // 2.   poll the API with the job number to check the status of the job.
        //      this will return the CSS when the job is complete.
        // 3.   if the job is not complete, wait a few seconds and try again.

        // if a generate job has been previously triggered, the API
        // will have returned a resultId which is stored in the DB.
        // this resultId can be used to check the status of the job
        // from the API and get the css when the job is complete.
        $resultId = $this->getResultId($urlModel);

        // if there is no resultId then no generate job has been triggered,
        // so trigger a new job via the API.
        if (!$resultId) {
            try {
                $resultId = $this->triggerGenerateJob($urlModel);
            } catch (\Exception $e) {
                return (new GeneratorResponse())
                    ->setSuccess(false)
                    ->setException($e);
            }

            if (!$resultId) {
                // If we can't trigger a job, it's likely because another job is active
                // This should be retryable so the job can try again later
                return (new GeneratorResponse())
                    ->setSuccess(false)
                    ->setException(new RetryableCssGenerationException(
                        'Failed to trigger generate job with criticalcss.com API - another job may be active. Will retry.'
                    ));
            }
        }

        $attemptCount = 0;

        while ($attemptCount < $this->maxAttempts) {

            // TEST MODE: Simulate different failure responses (only in developer mode)
            if ($this->isTestMode()) {
                $apiResponse = $this->simulateTestResponse();
            } else {
                // Normal operation: Poll the API for results using the resultId
                $apiResponse = $this->getResultsById($resultId);
            }

            // Check if the API response contains an error
            if ($apiResponse->hasError()) {
                $error = $apiResponse->getError();

                return (new GeneratorResponse())
                    ->setSuccess(false)
                    ->setException(new \Exception('Failed to get results from criticalcss.com API: ' . $error->toString()));
            }

            if ($apiResponse->isDone()) {

                // if the job is done, clear global job tracking
                $this->clearGlobalGenerateJob();

                if ($apiResponse->hasCss()) {
                    $cssStr = $apiResponse->getCss();

                    return (new GeneratorResponse())
                        ->setSuccess(true)
                        ->setCss(new CssModel($cssStr));
                } else {
                    // if the job is done but no css was returned,
                    // check if this is a retryable failure (like PENTHOUSE_TIMEOUT)
                    $resultStatus = $apiResponse->getResultStatus();

                    if ($this->isRetryableFailure($resultStatus)) {
                        // Clear the record data to allow a fresh attempt
                        $this->clearRecordData($urlModel);

                        Critter::getInstance()->log->info(
                            "Retryable failure '{$resultStatus}' for URL: {$urlModel->getAbsoluteUrl()}. Record cleared for retry.",
                            'generation'
                        );

                        return (new GeneratorResponse())
                            ->setSuccess(false)
                            ->setException(new RetryableCssGenerationException("Retryable failure from criticalcss.com: {$resultStatus}. Record cleared for retry."));
                    } else {
                        return (new GeneratorResponse())
                            ->setSuccess(false)
                            ->setException(new \Exception('No CSS returned from criticalcss.com API - ' . $resultStatus));
                    }
                }
            }

            $attemptCount++;
            sleep($this->attemptDelay);
        }

        Critter::getInstance()->log->error(
            "Failed to get critical CSS from criticalcss.com API after {$this->maxAttempts} attempts for URL: {$urlModel->getAbsoluteUrl()}",
            'generation'
        );

        // Clear global job tracking on timeout failure to allow new jobs
        $this->clearGlobalGenerateJob();

        throw new RetryableCssGenerationException("Failed to get critical css from criticalcss.com API after {$this->maxAttempts} attempts");
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        // Run validation to populate warnings/errors for display
        $this->validate();

        return [
            'generator' => $this,
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
            'pluginHandle' => Critter::getPluginHandle(),
        ];
    }

    /**
     * Validate API key
     */
    public function validateApiKey($attribute, $params): void
    {
        $apiKey = $this->getParsedApiKey();

        // Allow blank API key (may be set via environment variables or for testing)
        if (empty($apiKey)) {
            $this->addWarning($attribute, 'API key is blank. Ensure it is set via environment variable or configuration before generation.');
            return;
        }

        // Basic format validation - API keys should be reasonable length strings
        if (strlen($apiKey) < 10) {
            $this->addWarning($attribute, 'API key appears to be too short - please verify it is correct.');
        }
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = [
            [['apiKey'], 'string'],
            [['apiKey'], 'validateApiKey'],
            [['maxAttempts', 'attemptDelay', 'width', 'height'], 'integer', 'min' => 1],
            [['maxAttempts'], 'integer', 'min' => 1, 'max' => 50],
            [['attemptDelay'], 'integer', 'min' => 1, 'max' => 30],
            [['width'], 'integer', 'min' => 320, 'max' => 3840],
            [['height'], 'integer', 'min' => 240, 'max' => 2160],
        ];

        // Only add test mode validation rules when developer mode is enabled
        if (Critter::getInstance()->isDeveloperMode()) {
            $rules[] = [['testMode'], 'boolean'];
            $rules[] = [['testResultStatus'], 'string'];
            $rules[] = [['testResultStatus'], 'in', 'range' => [
                CriticalCssDotComApi::RESULT_STATUS_PENTHOUSE_TIMEOUT,
                CriticalCssDotComApi::RESULT_STATUS_SERVER_ERROR,
                CriticalCssDotComApi::RESULT_STATUS_CSS_TIMEOUT,
                CriticalCssDotComApi::RESULT_STATUS_HTML_TIMEOUT,
            ]];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => Critter::translate('API Key'),
            'maxAttempts' => Critter::translate('Max Attempts'),
            'attemptDelay' => Critter::translate('Attempt Delay'),
            'width' => Critter::translate('Viewport Width'),
            'height' => Critter::translate('Viewport Height'),
            'testMode' => Critter::translate('Enable Test Mode'),
            'testResultStatus' => Critter::translate('Test Result Status'),
        ];
    }

    private function getResultsById(string $id)
    {
        return $this->getApi()->getResults($id);
    }

    private function getResultId(UrlModel $url)
    {
        $record = Critter::getInstance()->requestRecords->getRecordByUrl($url);
        if ($record) {
            $data = Json::decode($record->data);
            return $data['resultId'] ?? null;
        }
        return null;
    }

    /**
     * Trigger a new generate job with the API, ensuring only one generate job at a time for API compliance
     * @return string|null The resultId of the triggered job, or null if it failed
     */
    private function triggerGenerateJob(UrlModel $urlModel): ?string
    {
        // Check if there's already an active generate job system-wide
        // This ensures compliance with API requirement and keeps implementation simple
        if ($this->hasActiveGenerateJob()) {
            Critter::getInstance()->log->info(
                "Active generate job already exists. Skipping new generate request to comply with API requirements.",
                'generation'
            );

            // Check if we can find an existing job for this specific URL
            $existingResultId = $this->getResultId($urlModel);
            if ($existingResultId) {
                Critter::getInstance()->log->info(
                    "Found existing resultId for URL: {$urlModel->getAbsoluteUrl()} - using existing job",
                    'generation'
                );
                return $existingResultId;
            }

            return null;
        }

        Critter::info(
            "Triggering new generate job for URL: {$urlModel->getAbsoluteUrl()}",
            'generation'
        );

        // Create global job tracking record before making API call
        $this->createGlobalGenerateJob($urlModel);

        try {
            // Make the API call to generate critical CSS
            $response = $this->getApi()->generate($urlModel, $this->width, $this->height);

            // Check if the API response contains an error
            if ($response->hasError()) {
                $error = $response->getError();

                // Clear global job tracking on API error
                $this->clearGlobalGenerateJob();

                Critter::error(
                    "Failed to generate critical CSS from criticalcss.com API: " . $error->toString(),
                    'generation'
                );

                throw new \Exception("Failed to generate critical CSS from criticalcss.com API: " . $error->toString());
            }

            $resultId = $response->getJobId();

            if (!$resultId) {
                // Clear global job tracking if no job ID returned
                $this->clearGlobalGenerateJob();

                Critter::getInstance()->log->error(
                    "Failed to generate critical css from criticalcss.com API: No job ID returned",
                    'generation'
                );
                return null;
            }

            // Store the resultId in the database
            $cssRequest = (new CssRequest())->setRequestUrl($urlModel);
            Critter::getInstance()->requestRecords->setData($cssRequest, ['resultId' => $resultId]);

            // Update global job tracking with the resultId
            $this->updateGlobalGenerateJob($resultId);

            Critter::getInstance()->log->info(
                "Successfully triggered generate job with resultId: $resultId for URL: {$urlModel->getAbsoluteUrl()}",
                'generation'
            );

            return $resultId;
        } catch (\Exception $e) {
            // Clear global job tracking on any exception
            $this->clearGlobalGenerateJob();

            Critter::error(
                "Exception while triggering generate job: {$e->getMessage()}",
                'generation'
            );

            throw $e;
        }
    }

    /**
     * Get the API client instance
     */
    private function getApi(): CriticalCssDotComApi
    {
        if ($this->api === null) {
            $apiKey = $this->getParsedApiKey();
            if (!$apiKey) {
                throw new \Exception('API key is required to create API client');
            }
            $this->api = new CriticalCssDotComApi($apiKey);
        }
        return $this->api;
    }

    /**
     * Get the parsed API key with environment variable support
     */
    public function getParsedApiKey(): ?string
    {
        return $this->apiKey ? App::parseEnv($this->apiKey) : null;
    }

    /**
     * Check if test mode is enabled and developer mode is active
     */
    private function isTestMode(): bool
    {
        return $this->testMode && Critter::getInstance()->isDeveloperMode();
    }

    /**
     * Check if the generator is properly configured and ready for generation
     */
    public function isReadyForGeneration(): bool
    {
        return !empty($this->getParsedApiKey());
    }

    /**
     * Check if an exception is an authentication error from the criticalcss.com API
     */
    private function isAuthError(\Exception $exception): bool
    {
        return strpos($exception->getMessage(), 'criticalcss.com API authentication failed') === 0;
    }

    /**
     * Check if a result status indicates a retryable failure
     */
    private function isRetryableFailure(?string $resultStatus): bool
    {
        $retryableStatuses = [
            CriticalCssDotComApi::RESULT_STATUS_PENTHOUSE_TIMEOUT,
            CriticalCssDotComApi::RESULT_STATUS_HTTP_SOCKET_HANG_UP
        ];

        return in_array($resultStatus, $retryableStatuses, true);
    }

    /**
     * Simulate test responses for development/testing
     */
    private function simulateTestResponse(): CriticalCssDotComResultsResponse
    {
        $resultStatus = $this->testResultStatus ?: CriticalCssDotComApi::RESULT_STATUS_PENTHOUSE_TIMEOUT;

        Critter::getInstance()->log->info(
            "TEST MODE: Simulating {$resultStatus} response",
            'generation'
        );

        return CriticalCssDotComResultsResponse::createFromResponse([
            'status' => CriticalCssDotComApi::STATUS_JOB_DONE,
            'resultStatus' => $resultStatus,
            'css' => null
        ]);
    }

    /**
     * Check if there's an active generate job system-wide
     * This ensures API compliance: "Do not request more than one /generate job at a time"
     */
    private function hasActiveGenerateJob(): bool
    {
        $cache = Craft::$app->getCache();

        $jobData = $cache->get(self::GLOBAL_JOB_CACHE_KEY);
        if (!$jobData) {
            return false;
        }

        // Check if the job is still active (has resultId but job might not be complete)
        $resultId = $jobData['resultId'] ?? null;
        if (!$resultId) {
            // Job initiated but no resultId yet - consider it active
            return true;
        }

        // Check if the job is still running by polling the API
        try {
            $apiResponse = $this->getResultsById($resultId);

            if ($apiResponse->hasError()) {
                // Error checking status - assume job is complete and clear tracking
                $this->clearGlobalGenerateJob();
                return false;
            }

            if ($apiResponse->isDone()) {
                // Job is complete - clear global tracking
                $this->clearGlobalGenerateJob();
                return false;
            }

            // Job is still running
            return true;
        } catch (\Exception $e) {
            // Error checking API - assume job is complete to allow new jobs
            Critter::getInstance()->log->warning(
                "Error checking global job status: {$e->getMessage()}. Clearing global tracking.",
                'generation'
            );
            $this->clearGlobalGenerateJob();
            return false;
        }
    }

    /**
     * Create global job tracking record
     */
    private function createGlobalGenerateJob(UrlModel $triggerUrl): void
    {
        $cache = Craft::$app->getCache();

        $jobData = [
            'triggerUrl' => $triggerUrl->getAbsoluteUrl(),
            'startTime' => date('Y-m-d H:i:s'),
            'resultId' => null
        ];

        // Store in cache with 5 minute duration (plenty for API job completion)
        $cache->set(self::GLOBAL_JOB_CACHE_KEY, $jobData, self::GLOBAL_JOB_CACHE_TTL);

        Critter::getInstance()->log->debug(
            "Created global generate job tracking for URL: {$triggerUrl->getAbsoluteUrl()}",
            'generation'
        );
    }

    /**
     * Update global job tracking with resultId
     */
    private function updateGlobalGenerateJob(string $resultId): void
    {
        $cache = Craft::$app->getCache();

        $jobData = $cache->get(self::GLOBAL_JOB_CACHE_KEY);
        if ($jobData) {
            $jobData['resultId'] = $resultId;
            $jobData['resultTime'] = date('Y-m-d H:i:s');

            // Update cache with same 5 minute TTL
            $cache->set(self::GLOBAL_JOB_CACHE_KEY, $jobData, self::GLOBAL_JOB_CACHE_TTL);

            Critter::getInstance()->log->debug(
                "Updated global generate job tracking with resultId: {$resultId}",
                'generation'
            );
        }
    }

    /**
     * Clear global job tracking
     */
    private function clearGlobalGenerateJob(): void
    {
        $cache = Craft::$app->getCache();

        $cache->delete(self::GLOBAL_JOB_CACHE_KEY);

        Critter::getInstance()->log->debug(
            "Cleared global generate job tracking",
            'generation'
        );
    }
}
