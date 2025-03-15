<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

interface GeneratorInterface
{
    public function generate(string $url, bool $storeResult = true): void;

    public function store(string $url, string $css): void;
}
