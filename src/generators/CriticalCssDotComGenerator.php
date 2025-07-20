<?php

namespace mijewe\critter\generators;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use mijewe\critter\Critter;
use mijewe\critter\drivers\apis\CriticalCssDotComApi;
use mijewe\critter\exceptions\MutexLockException;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;

class CriticalCssDotComGenerator extends BaseGenerator
{
    public string $handle = 'criticalcssdotcom';

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
    public int $attemptDelay = 2;

    /**
     * @var int Viewport width for critical CSS generation
     */
    public int $width = CriticalCssDotComApi::DEFAULT_WIDTH;

    /**
     * @var int Viewport height for critical CSS generation
     */
    public int $height = CriticalCssDotComApi::DEFAULT_HEIGHT;

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

        // Extract domain from URL for mutex locking
        $domain = $urlModel->getDomain();

        // Use Craft's mutex system for domain-level locking for entire job lifecycle
        // This ensures no new /generate requests until current job is complete
        $mutex = Craft::$app->getMutex();
        $lockName = Critter::getPluginHandle() . ':criticalcssdotcomgenerator:' . $domain;
        $mutexTimeout = Critter::getInstance()->getSettings()->mutexTimeout ?? 30;

        if (!$mutex->acquire($lockName, $mutexTimeout)) {
            // If we can't acquire the lock, another job is already running for this domain
            Craft::info(
                "Failed to acquire mutex lock for domain: $domain (timeout: {$mutexTimeout}s)",
                Critter::getPluginHandle()
            );

            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException(new MutexLockException(
                    Critter::translate('Another criticalcss.com job is already running for domain: ' . $domain . '. Please wait and try again.')
                ));
        }

        Craft::info(
            "Acquired mutex lock for domain: $domain",
            Critter::getPluginHandle()
        );

        try {
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
                $response = $this->getApi()->generate($urlModel, $this->width, $this->height);
                $resultId = $response->getJobId();

                if (!$resultId) {
                    throw new \Exception('Failed to generate critical css from criticalcss.com API');
                }

                Critter::getInstance()->requestRecords->setData($urlModel, ['resultId' => $resultId]);
            }

            $attemptCount = 0;

            while ($attemptCount < $this->maxAttempts) {

                $apiResponse = $this->getResultsById($resultId);

                if ($apiResponse->isDone()) {
                    if ($apiResponse->hasCss()) {
                        $cssStr = $apiResponse->getCss();

                        return (new GeneratorResponse())
                            ->setSuccess(true)
                            ->setCss(new CssModel($cssStr));
                    } else {

                        // if the job is done but no css was returned,
                        // this is an error.

                        return (new GeneratorResponse())
                            ->setSuccess(false)
                            ->setException(new \Exception('No CSS returned from criticalcss.com API'));
                    }
                }

                $attemptCount++;
                sleep($this->attemptDelay);
            }

            throw new \Exception('Failed to get critical css from criticalcss.com API');
        } finally {
            // Always release the mutex lock when job is complete (success or failure)
            Craft::info(
                "Releasing mutex lock for domain: $domain",
                Critter::getPluginHandle()
            );
            $mutex->release($lockName);
        }
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
        
        if (empty($apiKey)) {
            $this->addError($attribute, 'API key is required for criticalcss.com generator.');
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
        return [
            [['apiKey'], 'string'],
            [['apiKey'], 'required'],
            [['apiKey'], 'validateApiKey'],
            [['maxAttempts', 'attemptDelay', 'width', 'height'], 'integer', 'min' => 1],
            [['maxAttempts'], 'integer', 'min' => 1, 'max' => 50],
            [['attemptDelay'], 'integer', 'min' => 1, 'max' => 30],
            [['width'], 'integer', 'min' => 320, 'max' => 3840],
            [['height'], 'integer', 'min' => 240, 'max' => 2160],
        ];
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
}
