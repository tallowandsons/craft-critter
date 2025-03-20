<?php

namespace honchoagency\craftcriticalcssgenerator\models;

/**
 * Generator Response model
 */
class GeneratorResponse extends BaseResponse
{
    private CssModel $css;
    private \DateTime $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    public function setCss(CssModel $css): void
    {
        $this->css = $css;
    }

    public function getCss(): CssModel
    {
        return $this->css;
    }

    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}
