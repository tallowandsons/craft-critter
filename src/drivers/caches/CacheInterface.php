<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\caches;

use honchoagency\craftcriticalcssgenerator\models\UrlModel;

interface CacheInterface
{
    public function expireUrl(UrlModel $url): void;
}
