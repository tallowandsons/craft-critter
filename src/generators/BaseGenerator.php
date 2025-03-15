<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;

class BaseGenerator implements GeneratorInterface
{
    public function generate(string $url, bool $storeResult = true): void {}

    public function store(string $url, string $css): void
    {
        Critical::getInstance()->storage->save($url, $css);
    }
}
