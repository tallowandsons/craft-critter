<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use Craft;
use craft\helpers\Json;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\drivers\apis\CriticalCssDotComApi;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use honchoagency\craftcriticalcssgenerator\records\UriRecord;

class CriticalCssDotComGenerator extends BaseGenerator
{

    // the maximum number of times to poll the API for the results
    // of a generate job before giving up.
    public int $maxAttempts = 10;

    // the number of seconds to wait between each poll attempt
    public int $attemptDelay = 2;

    public CriticalCssDotComApi $api;

    public function __construct()
    {
        $this->api = new CriticalCssDotComApi();
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): CssModel
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

            $this->updateUriRecord($urlModel, null, ['resultId' => $resultId]);
        }

        $attemptCount = 0;

        while ($attemptCount < $this->maxAttempts) {

            $response = $this->getResultsById($resultId);

            if ($response->isDone()) {
                if ($response->hasCss()) {
                    $cssStr = $response->getCss();
                    return new CssModel($cssStr);
                }
            }

            $attemptCount++;
            sleep($this->attemptDelay);
        }

        throw new \Exception('Failed to get critical css from criticalcss.com API');
    }

    private function getResultsById(string $id)
    {
        return $this->api->getResults($id);
    }

    private function getResultId(UrlModel $url)
    {
        $record = Critical::getInstance()->uriRecords->getRecordByUrl($url);
        if ($record) {
            $data = Json::decode($record->data);
            return $data['resultId'] ?? null;
        }
        return null;
    }
}
