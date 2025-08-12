<?php

namespace tallowandsons\critter\models;

class CssValidationResult
{
    public const STATUS_OK = 'ok';
    public const STATUS_SANITIZED = 'sanitized';
    public const STATUS_BLOCKED = 'blocked';

    public string $status = self::STATUS_OK;
    public ?CssModel $css = null; // sanitized or original
    public array $violations = [];

    public static function ok(CssModel $css): self
    {
        $r = new self();
        $r->status = self::STATUS_OK;
        $r->css = $css;
        return $r;
    }

    public static function sanitized(CssModel $css, array $violations): self
    {
        $r = new self();
        $r->status = self::STATUS_SANITIZED;
        $r->css = $css;
        $r->violations = $violations;
        return $r;
    }

    public static function blocked(array $violations): self
    {
        $r = new self();
        $r->status = self::STATUS_BLOCKED;
        $r->violations = $violations;
        return $r;
    }

    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }
    public function isSanitized(): bool
    {
        return $this->status === self::STATUS_SANITIZED;
    }
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }
}
