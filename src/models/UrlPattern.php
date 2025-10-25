<?php

namespace tallowandsons\critter\models;

use Craft;
use craft\base\Model;

/**
 * Url Pattern model
 */
class UrlPattern extends Model
{
    private bool $enabled = false;
    private string $pattern = '';

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['enabled', 'pattern'], 'required'],
            ['pattern', 'string', 'max' => 255],
        ]);
    }

    // =============
    // ===== enabled
    // =============
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        // Handle various truthy/falsy values for enabled flag
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    // =============
    // ===== pattern
    // =============
    public function hasPattern(): bool
    {
        return !empty($this->pattern);
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $pattern, bool $normalise = true): self
    {
        if ($normalise) {
            // trim leading and trailing whitespace
            $pattern = trim($pattern);

            // add delimiters if not present
            if (@preg_match($pattern, '') === false) {
                $pattern = '/' . str_replace('/', '\/', $pattern) . '/';
            }
        }

        $this->pattern = $pattern;
        return $this;
    }

    public function patternMatches(UrlModel $urlModel): bool
    {
        $subject = $urlModel->getPath();
        $match = (bool) preg_match($this->pattern, $subject);
        return $match;
    }

    // ===============
    // ===== factories
    // ===============
    public static function createFromArray(array $data): self
    {
        return (new self())
            ->setEnabled($data['enabled'] ?? false)
            ->setPattern($data['pattern'] ?? '');
    }
}
