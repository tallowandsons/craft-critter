<?php

namespace honchoagency\craftcriticalcssgenerator\jobs;

use craft\queue\BaseJob;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

/**
 * Generate Critical Css Job queue job
 */
class GenerateCriticalCssJob extends BaseJob
{

    public UrlModel $url;
    public bool $storeResult = true;

    function execute($queue): void
    {
        $generatorClass = Critical::getInstance()->settings->generatorType;
        $generator = new $generatorClass();
        $generator->generate($this->url, $this->storeResult);
    }

    protected function defaultDescription(): ?string
    {
        return 'Generating critical css for ' . $this->url->getUrl();
    }
}
