<?php

namespace mijewe\critter\models;

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
        $this->css = new CssModel();
        $this->timestamp = new \DateTime();
        $this->exception = null;
    }

    public function setCss(CssModel $css): self
    {
        $this->css = $css;
        return $this;
    }

    public function getCss(): CssModel
    {
        return $this->css;
    }

    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setException(\Exception $exception): self
    {
        $this->exception = $exception;
        return $this;
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
