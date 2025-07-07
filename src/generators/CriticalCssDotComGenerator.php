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

    // the maximum number of times to poll the API for the results
    // of a generate job before giving up.
    public int $maxAttempts = 10;

    // the number of seconds to wait between each poll attempt
    public int $attemptDelay = 2;

    // viewport width for critical CSS generation
    public int $width = CriticalCssDotComApi::DEFAULT_WIDTH;

    // viewport height for critical CSS generation
    public int $height = CriticalCssDotComApi::DEFAULT_HEIGHT;

    // the API key for the criticalcss.com account
    public ?string $apiKey;

    public CriticalCssDotComApi $api;

    public function __construct()
    {
        $generatorSettings = Critter::getInstance()->settings->generatorSettings ?? [];
        $apiKey = $generatorSettings['apiKey'] ?? null;
        $this->apiKey = $apiKey ? App::parseEnv($apiKey) : null;

        // Load max attempts and attempt delay from settings, with fallback to defaults
        $this->maxAttempts = (int)($generatorSettings['maxAttempts'] ?? $this->maxAttempts);
        $this->attemptDelay = (int)($generatorSettings['attemptDelay'] ?? $this->attemptDelay);

        // Load viewport dimensions from settings, with fallback to defaults
        $this->width = (int)($generatorSettings['width'] ?? $this->width);
        $this->height = (int)($generatorSettings['height'] ?? $this->height);

        if ($this->apiKey) {
            $this->api = new CriticalCssDotComApi($this->apiKey);
        }

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
        if (!$this->apiKey) {
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
                $response = $this->api->generate($urlModel, $this->width, $this->height);
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
        return [
            'apiKey' => $this->apiKey,
            'maxAttempts' => $this->maxAttempts,
            'attemptDelay' => $this->attemptDelay,
            'width' => $this->width,
            'height' => $this->height,
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
        ];
    }

    private function getResultsById(string $id)
    {
        return $this->api->getResults($id);
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
}
