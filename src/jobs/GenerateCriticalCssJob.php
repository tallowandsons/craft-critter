<?php

namespace honchoagency\craftcriticalcssgenerator\jobs;

use craft\queue\BaseJob;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\UrlModel
use honchoagency\craftcriticalcssgenerator\records\UriRecord;

/**
 * Generate Critical Css Job queue job
 */
class GenerateCriticalCssJob extends BaseJob
{

    public UrlModel $url;
    public bool $storeResult = true;

    function execute($queue): void
    {
        Critical::getInstance()->generator->generate($this->url, $this->storeResult);
    }

    protected function defaultDescription(): ?string
    {
        return 'Generating critical css for ' . $this->url->getUrl();
    }
}
