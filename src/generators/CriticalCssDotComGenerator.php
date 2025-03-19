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

    public int $timeout = 60;
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

        $resultId = null;

        // get a resultId from the DB based on the url
        $record = Critical::getInstance()->uriRecords->getRecordByUrl($urlModel);
        if ($record) {
            $data = Json::decode($record->data);
            $resultId = $data['resultId'] ?? null;
        }

        // if there is no resultId, then no job has been triggered, so
        // trigger a new job via the API and return an empty string
        if (!$resultId) {
            $response = $this->api->generate($urlModel);

            $jobId = $response->getJobId();

            Critical::getInstance()->uriRecords->saveOrUpdateUrl($urlModel, UriRecord::STATUS_PENDING, ['resultId' => $jobId]);

            return new CssModel();
        }

        // if there is a resultId, then we can check the status of the job via the API
        // if the job is complete, we can get the css from the API
        // if the job is not complete, we can return an empty string

        $response = $this->getResults($resultId);

        if ($response->isDone()) {
            if ($response->hasCss()) {
                $cssStr = $response->getCss();
                return new CssModel($cssStr);
            }
        }

        // if there is no resultId, we can trigger a new job via the API
        // then save the resultId to the DB, and return an empty string

        return new CssModel();
    }

    public function getResults(string $id)
    {
        return $this->api->getResults($id);
    }
}
