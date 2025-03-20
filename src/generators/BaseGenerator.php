<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use honchoagency\craftcriticalcssgenerator\records\UriRecord;

class BaseGenerator implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url, bool $storeResult = true, bool $resolveCache = true): void
    {
        $this->addOrUpdateUriRecord($url, UriRecord::STATUS_PENDING);

        $css = $this->getCriticalCss($url);

        if (!$css->isEmpty()) {
            if ($storeResult) {
                $this->store($url, $css);
            }

            if ($resolveCache) {
                $this->resolveCache($url);
            }
        }
    }

    /**
     * Get the critical CSS for the given URL.
     */
    protected function getCriticalCss(UrlModel $url): CssModel
    {
        return new CssModel();
    }

    /**
     * @inheritdoc
     */
    public function store(UrlModel $url, CssModel $css): void
    {
        Critical::getInstance()->storage->save($url, $css);
    }

    /**
     * @inheritdoc
     */
    public function resolveCache(UrlModel $url): void
    {
        Critical::getInstance()->cache->resolveCache($url);
    }

    public function addOrUpdateUriRecord(UrlModel $url, ?string $status, ?array $data = null): bool
    {
        return Critical::getInstance()->uriRecords->saveOrUpdateUrl($url);
    }
}
