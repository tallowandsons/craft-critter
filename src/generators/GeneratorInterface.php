<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\GeneratorResponse;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

interface GeneratorInterface
{
    /**
     * Generate the critical CSS for the given URL, then optionally store the result and/or resolve the cache.
     */
    public function generate(UrlModel $url, bool $storeResult = true, bool $resolveCache = true): GeneratorResponse;

    /**
     * Store the critical CSS for the given URL accorging to the storage driver and settings.
     */
    public function store(UrlModel $url, CssModel $css): void;

    /**
     * Clear, expire, or refresh the cached page according to the cache driver and settings
     */
    public function resolveCache(UrlModel $url): void;
}
