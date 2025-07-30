<?php

namespace tallowandsons\critter\generators;

use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\GeneratorResponse;
use tallowandsons\critter\models\UrlModel;

class DummyGenerator extends BaseGenerator
{
    private string $css = "body { background-color: pink; }";

    public static function displayName(): string
    {
        return 'Dummy Generator';
    }

    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        $generatorResponse = new GeneratorResponse();
        $generatorResponse->setSuccess(true);
        $generatorResponse->setCss(new CssModel($this->css));
        return $generatorResponse;
    }
}
