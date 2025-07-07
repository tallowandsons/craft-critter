<?php

namespace mijewe\critter\generators;

use mijewe\critter\models\CssModel;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;

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
