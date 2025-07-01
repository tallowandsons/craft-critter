<?php

namespace mijewe\craftcriticalcssgenerator\services;

use Craft;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\generators\GeneratorInterface;
use mijewe\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use mijewe\craftcriticalcssgenerator\models\CssRequest;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
use mijewe\craftcriticalcssgenerator\records\RequestRecord;
use yii\base\Component;

/**
 * Generator service
 */
class GeneratorService extends Component
{

    public GeneratorInterface $generator;

    public function __construct()
    {
        $generatorClass = Critical::getInstance()->settings->generatorType;
        $this->generator = new $generatorClass();
    }

    /**
     * Start generating critical css for a url, optionally using the queue
     */
    public function startGenerate(CssRequest $cssRequest, bool $useQueue = true, bool $storeResult = true): void
    {
        if ($useQueue) {
            $this->queueIfNewJob($cssRequest, $storeResult);
        } else {
            $this->generate($cssRequest, $storeResult);
        }
    }

    /**
     * Generate critical css for a url.
     * This is different from startGenerate as it will always generate the css immediately, not using the queue.
     */
    public function generate(CssRequest $cssRequest, bool $storeResult = true, bool $resolveCache = true): void
    {

        $url = $cssRequest->getUrl();

        // set the uri record status to 'generating'
        Critical::getInstance()->requestRecords->setStatus($url, RequestRecord::STATUS_GENERATING);

        // generate the critical css
        $response = $this->generator->generate($url);

        if ($response->isSuccess()) {

            // update URI record
            Critical::getInstance()->requestRecords->createOrUpdateRecord($url, RequestRecord::STATUS_COMPLETE, null, null, $response->getTimestamp());

            // store the css
            if ($storeResult) {
                Critical::getInstance()->storage->save($cssRequest, $response->getCss());
            }

            // resolve the cache
            if ($resolveCache) {
                Critical::getInstance()->cache->resolveCache($cssRequest);
            }
        } else {
            Critical::getInstance()->requestRecords->setStatus($url, RequestRecord::STATUS_ERROR);

            // throw an exception if the response has one.
            // this will fail the queue job and report the error.
            if ($response->hasException()) {
                throw $response->getException();
            }
        }
    }

    private function queueIfNewJob(CssRequest $cssRequest, bool $storeResult): void
    {
        $url = $cssRequest->getUrl();

        // don't queue a new job if there is already one in the queue
        // for this URL
        if ($this->isInQueue($url)) {
            return;
        }

        // otherwise, create a new job, queue it, and update the record
        $job = new GenerateCriticalCssJob([
            'cssRequest' => $cssRequest,
            'storeResult' => $storeResult
        ]);

        if ($queueJobId = Craft::$app->queue->push($job)) {
            Critical::getInstance()->requestRecords->createOrUpdateRecord($url, RequestRecord::STATUS_QUEUED, ['jobId' => $queueJobId], new \DateTime());
        }
    }

    private function isInQueue(UrlModel $url): bool
    {
        $uriRecord = Critical::getInstance()->requestRecords->getRecordByUrl($url);

        if ($uriRecord === null) {
            return false;
        }

        return $uriRecord->isInQueue();
    }
}
