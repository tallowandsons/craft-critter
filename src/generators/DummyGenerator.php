<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;

class DummyGenerator extends BaseGenerator
{
    private string $css = "body { background-color: pink; }";

    public function generate(string $url, bool $storeResult = true): void
    {
        if ($storeResult) {
            Critical::getInstance()->storage->save($url, $this->css);
        }
    }
}
