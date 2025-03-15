<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

interface GeneratorInterface
{
    public function generate(UrlModel $url, bool $storeResult = true): void;

    public function store(UrlModel $url, CssModel $css): void;
}
