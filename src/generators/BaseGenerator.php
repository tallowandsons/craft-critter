<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class BaseGenerator implements GeneratorInterface
{
    public function generate(UrlModel $url, bool $storeResult = true): void {}

    public function store(UrlModel $url, CssModel $css): void
    {
        Critical::getInstance()->storage->save($url, $css);
    }

    public function expireCache(UrlModel $url): void
    {
        Critical::getInstance()->cache->expireUrl($url);
    }
}
