<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class DummyGenerator extends BaseGenerator
{
    private string $css = "body { background-color: pink; }";

    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url, bool $storeResult = true, bool $resolveCache = true): void
    {
        if ($storeResult) {
            Critical::getInstance()->storage->save($url, new CssModel($this->css));
        }

        if ($resolveCache) {
            $this->resolveCache($url);
        }
    }
}
