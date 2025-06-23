<?php

namespace honchoagency\craftcriticalcssgenerator\models;

/**
 * Generator Response model
 */
class GeneratorResponse extends BaseResponse
{
    private CssModel $css;
    private \DateTime $timestamp;
    private ?\Exception $exception;

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

    public function setException(\Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    public function hasException(): bool
    {
        return $this->getException() !== null;
    }
}
