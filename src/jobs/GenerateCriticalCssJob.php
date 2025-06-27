<?php

namespace mijewe\craftcriticalcssgenerator\jobs;

use craft\queue\BaseJob;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\models\CssRequest;

/**
 * Generate Critical Css Job queue job
 */
class GenerateCriticalCssJob extends BaseJob
{

    public CssRequest $cssRequest;
    public bool $storeResult = true;

    function execute($queue): void
    {
        Critical::getInstance()->generator->generate($this->cssRequest, $this->storeResult);
    }

    protected function defaultDescription(): ?string
    {
        $url = $this->cssRequest->getUrl()->getAbsoluteUrl();
        return 'Generating critical css for ' . $url;
    }
}
