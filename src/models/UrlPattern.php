<?php

namespace tallowandsons\critter\models;

use Craft;
use craft\base\Model;
use tallowandsons\critter\Critter;

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

    public function getPattern(bool $normalise = true): string
    {
        return $normalise ? $this->normalisePattern() : $this->pattern;
    }

    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function patternMatches(UrlModel $urlModel): bool
    {
        // abort early if pattern is not valid
        if (!$this->patternIsValid()) {
            return false;
        }

        $subject = $urlModel->getPath();

        // check if pattern matches the subject
        // (suppress errors for invalid patterns - should not occur due to prior validation)
        $match = @preg_match($this->getPattern(), $subject);

        // return whether there was a match (cast int to bool)
        return (bool) $match;
    }

    private function patternIsValid(): bool
    {
        // if no pattern provided, use the normalised model pattern
        $pattern = $this->getPattern();

        try {
            return preg_match($pattern, '') !== false;
        } catch (\Throwable $th) {
            Critter::error("Invalid regex pattern in UrlPattern: " . ($pattern), __METHOD__);
            return false;
        }
    }

    /**
     * Normalise a pattern by trimming whitespace and adding delimiters if needed
     */
    private function normalisePattern(): string
    {
        // trim leading and trailing whitespace
        $pattern = trim($this->pattern);

        // add forward slash delimiters if not present
        if (strlen($pattern) < 2 || $pattern[0] !== '/' || $pattern[-1] !== '/') {
            // Escape forward slashes and wrap with delimiters
            $pattern = '/' . str_replace('/', '\/', $pattern) . '/';
        }

        return $pattern;
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
