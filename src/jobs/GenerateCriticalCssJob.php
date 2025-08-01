<?php

namespace tallowandsons\critter\jobs;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\CssRequest;

/**
 * Generate Critical Css Job queue job
 */
class GenerateCriticalCssJob extends GenerateCssBaseJob
{
    public CssRequest $cssRequest;
    public bool $storeResult = true;

    /**
     * @inheritdoc
     */
    protected function performCssGeneration(): void
    {
        Critter::getInstance()->generator->generate($this->cssRequest, $this->storeResult);
    }

    /**
     * @inheritdoc
     */
    protected function getJobContext(): string
    {
        return 'URL: ' . $this->cssRequest->getUrl()->getAbsoluteUrl();
    }

    /**
     * @inheritdoc
     */
    protected function createRetryJob(int $nextAttempt): GenerateCssBaseJob
    {
        return new self([
            'cssRequest' => $this->cssRequest,
            'storeResult' => $this->storeResult,
            'retryAttempt' => $nextAttempt
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getJobDescription(): string
    {
        return 'Generating critical css for ' . $this->cssRequest->getUrl()->getAbsoluteUrl();
    }
}
