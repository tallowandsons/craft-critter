<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\GeneratorResponse;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class DummyGenerator extends BaseGenerator
{
    private string $css = "body { background-color: pink; }";

    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        $generatorResponse = new GeneratorResponse();
        $generatorResponse->setSuccess(true);
        $generatorResponse->setCss(new CssModel($this->css));
        return $generatorResponse;
    }
}
