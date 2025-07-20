<?php

namespace mijewe\critter\services;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\generators\GeneratorInterface;
use mijewe\critter\generators\NoGenerator;
use mijewe\critter\helpers\GeneratorHelper;
use mijewe\critter\jobs\GenerateCriticalCssJob;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;
use yii\base\Component;

/**
 * Generator service
 */
class GeneratorService extends Component
{

    public GeneratorInterface $generator;

    public function __construct()
    {
        $generatorClass = Critter::getInstance()->settings->generatorType;

        // Validate the generator class using the GeneratorHelper
        if (!GeneratorHelper::isValidGenerator($generatorClass)) {
            throw new \InvalidArgumentException("Invalid generator class: {$generatorClass}");
        }

        $this->generator = new $generatorClass();
    }

    /**
     * Start generating critical css for a url, optionally using the queue
     */
    public function startGenerate(CssRequest $cssRequest, bool $useQueue = true, bool $storeResult = true): void
    {
        // Early abort: Don't start generation if NoGenerator is active
        if (NoGenerator::isActive()) {
            Craft::info(
                'Skipping critical CSS generation - NoGenerator is active (URL: ' . $cssRequest->getUrl()->getAbsoluteUrl() . ')',
                Critter::getPluginHandle()
            );
            return;
        }

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
        Critter::getInstance()->requestRecords->setStatus($url, RequestRecord::STATUS_GENERATING);

        // generate the critical css
        $response = $this->generator->generate($url);

        if ($response->isSuccess()) {

            // update URI record
            Critter::getInstance()->requestRecords->createOrUpdateRecord($url, RequestRecord::STATUS_COMPLETE, null, null, $response->getTimestamp());

            // store the css
            if ($storeResult) {
                Critter::getInstance()->storage->save($cssRequest, $response->getCss());
            }

            // resolve the cache
            if ($resolveCache) {
                Critter::getInstance()->cache->resolveCache($cssRequest);
            }
        } else {
            Critter::getInstance()->requestRecords->setStatus($url, RequestRecord::STATUS_ERROR);

            // throw an exception if the response has one.
            // this will fail the queue job and report the error.
            if ($response->hasException()) {
                throw $response->getException();
            }
        }
    }

    private function queueIfNewJob(CssRequest $cssRequest, bool $storeResult): void
    {
        // Early abort: Don't queue jobs if NoGenerator is active
        if (NoGenerator::isActive()) {
            Craft::info(
                'Skipping queue job creation - NoGenerator is active (URL: ' . $cssRequest->getUrl()->getAbsoluteUrl() . ')',
                Critter::getPluginHandle()
            );
            return;
        }

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
            Critter::getInstance()->requestRecords->createOrUpdateRecord($url, RequestRecord::STATUS_QUEUED, ['jobId' => $queueJobId], new \DateTime());
        }
    }

    private function isInQueue(UrlModel $url): bool
    {
        $uriRecord = Critter::getInstance()->requestRecords->getRecordByUrl($url);

        if ($uriRecord === null) {
            return false;
        }

        return $uriRecord->isInQueue();
    }
}
