<?php

namespace tallowandsons\critter\services;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\generators\GeneratorInterface;
use tallowandsons\critter\generators\NoGenerator;
use tallowandsons\critter\helpers\GeneratorHelper;
use tallowandsons\critter\jobs\GenerateCriticalCssJob;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\models\UrlModel;
use tallowandsons\critter\records\RequestRecord;
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
            Critter::getInstance()->log->info(
                'Skipping critical CSS generation - NoGenerator is active',
                'generation'
            );
            return;
        }

        // Early abort: Don't start generation if generator is not ready
        if (!$this->generator->isReadyForGeneration()) {
            $generatorClass = get_class($this->generator);
            Critter::getInstance()->log->warning(
                "Skipping critical CSS generation - {$generatorClass} is not properly configured or ready for generation",
                'generation'
            );
            return;
        }

        $url = $cssRequest->getUrl()->getAbsoluteUrl();
        $generatorClass = get_class($this->generator);
        Critter::getInstance()->log->logGenerationStart($url, $generatorClass);

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
        $urlString = $url->getAbsoluteUrl();
        $generatorClass = get_class($this->generator);
        $startTime = microtime(true);

        Critter::getInstance()->log->debug("Starting direct generation for '{$urlString}'", 'generation');

        // set the uri record status to 'generating'
        Critter::getInstance()->requestRecords->setStatus($cssRequest, RequestRecord::STATUS_GENERATING);

        // generate the critical css
        $response = $this->generator->generate($url);

        if ($response->isSuccess()) {
            $duration = microtime(true) - $startTime;
            Critter::getInstance()->log->logGenerationComplete($urlString, $generatorClass, $duration);

            // update URI record
            Critter::getInstance()->requestRecords->createOrUpdateRecord($cssRequest, RequestRecord::STATUS_COMPLETE, null, null, $response->getTimestamp());

            // nullify expiry date since fresh CSS was generated
            Critter::getInstance()->requestRecords->nullifyExpiryDate($cssRequest);

            // store the css
            if ($storeResult) {
                Critter::getInstance()->storage->save($cssRequest, $response->getCss());
                Critter::getInstance()->log->logStorageOperation('save', $urlString, 'CraftCacheStorage');
            }

            // resolve the cache
            if ($resolveCache) {
                Critter::getInstance()->cache->resolveCache($cssRequest);
            }
        } else {
            $errorMessage = $response->hasException() ? $response->getException()->getMessage() : 'Unknown error';
            Critter::getInstance()->log->logGenerationFailure($urlString, $generatorClass, $errorMessage);

            Critter::getInstance()->requestRecords->setStatus($cssRequest, RequestRecord::STATUS_ERROR);

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
            Critter::getInstance()->log->info(
                'Skipping queue job creation - NoGenerator is active',
                'queue'
            );
            return;
        }

        // Early abort: Don't queue jobs if generator is not ready
        if (!$this->generator->isReadyForGeneration()) {
            $generatorClass = get_class($this->generator);
            Critter::getInstance()->log->warning(
                "Skipping queue job creation - {$generatorClass} is not properly configured or ready for generation",
                'queue'
            );
            return;
        }

        $url = $cssRequest->getUrl();
        $urlString = $url->getAbsoluteUrl();

        // don't queue a new job if there is already one in the queue
        // for this URL
        if ($this->isInQueue($cssRequest)) {
            Critter::getInstance()->log->debug("Job already queued for '{$urlString}'", 'queue');
            return;
        }

        // otherwise, create a new job, queue it, and update the record
        $job = new GenerateCriticalCssJob([
            'cssRequest' => $cssRequest,
            'storeResult' => $storeResult
        ]);

        if ($queueJobId = Craft::$app->queue->push($job)) {
            Critter::getInstance()->log->logQueueJob('created', $urlString, $queueJobId);
            Critter::getInstance()->requestRecords->createOrUpdateRecord($cssRequest, RequestRecord::STATUS_QUEUED, ['jobId' => $queueJobId], new \DateTime());
        } else {
            Critter::getInstance()->log->error("Failed to queue job for '{$urlString}'", 'queue');
        }
    }

    private function isInQueue(CssRequest $cssRequest): bool
    {
        $record = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest);

        if ($record === null) {
            return false;
        }

        return $record->isInQueue();
    }
}
