<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use honchoagency\craftcriticalcssgenerator\models\CssRequest;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use honchoagency\craftcriticalcssgenerator\records\UriRecord;
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
        Critical::getInstance()->uriRecords->setStatus($url, UriRecord::STATUS_GENERATING);

        // generate the critical css
        $response = $this->generator->generate($url, false);

        if ($response->isSuccess()) {

            // update URI record
            Critical::getInstance()->uriRecords->createOrUpdateRecord($url, UriRecord::STATUS_COMPLETE, null, null, $response->getTimestamp());

            // store the css
            if ($storeResult) {
                Critical::getInstance()->storage->save($cssRequest, $response->getCss());
            }

            // resolve the cache
            if ($resolveCache) {
                Critical::getInstance()->cache->resolveCache($cssRequest);
            }
        } else {
            Critical::getInstance()->uriRecords->setStatus($url, UriRecord::STATUS_ERROR);
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
            Critical::getInstance()->uriRecords->createOrUpdateRecord($url, UriRecord::STATUS_QUEUED, ['jobId' => $queueJobId], new \DateTime());
        }
    }

    private function isInQueue(UrlModel $url): bool
    {
        $uriRecord = Critical::getInstance()->uriRecords->getRecordByUrl($url);

        if ($uriRecord === null) {
            return false;
        }

        return $uriRecord->isInQueue();
    }
}
