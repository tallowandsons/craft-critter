<?php

namespace mijewe\craftcriticalcssgenerator\generators;

use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

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
