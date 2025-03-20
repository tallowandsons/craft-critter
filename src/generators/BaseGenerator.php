<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\GeneratorResponse;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class BaseGenerator implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url, bool $storeResult = true, bool $resolveCache = true): GeneratorResponse
    {
        $response = $this->getCriticalCss($url);

        if ($response->isSuccess()) {
            if ($storeResult) {
                $this->store($url, $response->getCss());
            }

            if ($resolveCache) {
                $this->resolveCache($url);
            }
        }

        return $response;
    }

    /**
     * Get the critical CSS for the given URL.
     */
    protected function getCriticalCss(UrlModel $url): GeneratorResponse
    {
        return new GeneratorResponse();
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
}
