<?php

namespace mijewe\craftcriticalcssgenerator\generators;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\drivers\apis\CriticalCssDotComApi;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

class CriticalCssDotComGenerator extends BaseGenerator
{

    public string $handle = 'criticalcssdotcom';

    // the maximum number of times to poll the API for the results
    // of a generate job before giving up.
    public int $maxAttempts = 10;

    // the number of seconds to wait between each poll attempt
    public int $attemptDelay = 2;

    // the API key for the criticalcss.com account
    public ?string $apiKey;

    public CriticalCssDotComApi $api;

    public function __construct()
    {
        $generatorSettings = Critical::getInstance()->settings->generatorSettings ?? [];
        $apiKey = $generatorSettings['apiKey'] ?? null;
        $this->apiKey = $apiKey ? App::parseEnv($apiKey) : null;

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
        return Craft::t('critical-css-generator', 'criticalcss.com Generator');
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {

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
            $response = $this->api->generate($urlModel);
            $resultId = $response->getJobId();

            if (!$resultId) {
                throw new \Exception('Failed to generate critical css from criticalcss.com API');
            }

            Critical::getInstance()->requestRecords->setData($urlModel, ['resultId' => $resultId]);
        }

        $attemptCount = 0;

        while ($attemptCount < $this->maxAttempts) {

            $apiResponse = $this->getResultsById($resultId);

            if ($apiResponse->isDone()) {
                if ($apiResponse->hasCss()) {
                    $cssStr = $apiResponse->getCss();

                    $generatorResponse = new GeneratorResponse();
                    $generatorResponse->setSuccess(true);
                    $generatorResponse->setCss(new CssModel($cssStr));
                    return $generatorResponse;
                } else {

                    // if the job is done but no css was returned,
                    // this is an error.

                    $generatorResponse = new GeneratorResponse();
                    $generatorResponse->setSuccess(false);
                    // $generatorResponse->setError('No CSS returned from criticalcss.com API');
                    return $generatorResponse;
                }
            }

            $attemptCount++;
            sleep($this->attemptDelay);
        }

        throw new \Exception('Failed to get critical css from criticalcss.com API');
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
            'settings' => Critical::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile('critical-css-generator'),
        ];
    }

    private function getResultsById(string $id)
    {
        return $this->api->getResults($id);
    }

    private function getResultId(UrlModel $url)
    {
        $record = Critical::getInstance()->requestRecords->getRecordByUrl($url);
        if ($record) {
            $data = Json::decode($record->data);
            return $data['resultId'] ?? null;
        }
        return null;
    }
}
