<?php

namespace tallowandsons\critter\models;

use craft\base\Model;
use craft\elements\Entry;
use craft\models\Section;
use Craft;
use tallowandsons\critter\helpers\CompatibilityHelper;

/**
 * Tag Model
 *
 * Represents a tag string used for cache record organization.
 * Supports entry tags (e.g., 'entry:123') and section tags (e.g., 'section:blog').
 */
class Tag extends Model
{
    public const TYPE_ENTRY = 'entry';
    public const TYPE_SECTION = 'section';
    public const TYPE_UNKNOWN = 'unknown';

    private string $type;
    private ?string $identifier = null;

    public function __construct(?string $tagString = null)
    {
        parent::__construct();
        $this->parseTagString($tagString);
    }

    /**
     * Create a Tag instance from a tag string
     *
     * @param string|null $tagString The tag string to parse (e.g., 'entry:123', 'section:blog')
     * @return static
     */
    public static function fromString(?string $tagString): static
    {
        return new static($tagString);
    }

    /**
     * Create a Tag instance from an Entry
     *
     * @param Entry $entry The entry to create a tag for
     * @return static
     */
    public static function fromEntry(Entry $entry): static
    {
        return new static("entry:{$entry->id}");
    }

    /**
     * Get the tag type
     *
     * @return string One of: 'entry', 'section', 'unknown'
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the identifier portion of the tag
     *
     * @return string|null The identifier (e.g., '123' for 'entry:123', 'blog' for 'section:blog')
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Check if this is an entry tag
     *
     * @return bool
     */
    public function isEntry(): bool
    {
        return $this->type === self::TYPE_ENTRY;
    }

    /**
     * Check if this is a section tag
     *
     * @return bool
     */
    public function isSection(): bool
    {
        return $this->type === self::TYPE_SECTION;
    }

    /**
     * Check if this tag is valid and has a known type
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->type !== self::TYPE_UNKNOWN && $this->identifier !== null;
    }

    /**
     * Get the entry associated with this tag (if it's an entry tag)
     *
     * @param int $siteId The site ID to search within
     * @return Entry|null The entry if found and tag is valid, null otherwise
     */
    public function getEntry(int $siteId): ?Entry
    {
        if (!$this->isEntry() || !$this->identifier) {
            return null;
        }

        $entryId = (int) $this->identifier;
        if ($entryId <= 0) {
            return null;
        }

        return Entry::find()
            ->id($entryId)
            ->siteId($siteId)
            ->status(null) // Include disabled entries
            ->one();
    }

    /**
     * Get the section associated with this tag (if it's a section tag)
     *
     * @return Section|null The section if found and tag is valid, null otherwise
     */
    public function getSection(): ?Section
    {
        if (!$this->isSection() || !$this->identifier) {
            return null;
        }



        return CompatibilityHelper::getSectionByHandle($this->identifier);
    }

    /**
     * Convert back to string representation
     *
     * @return string|null The tag string or null if invalid
     */
    public function toString(): ?string
    {
        if (!$this->isValid()) {
            return null;
        }

        return "{$this->type}:{$this->identifier}";
    }

    /**
     * Parse a tag string and set internal properties
     *
     * @param string|null $tagString The tag string to parse
     */
    private function parseTagString(?string $tagString): void
    {
        if (!$tagString || !str_contains($tagString, ':')) {
            $this->type = self::TYPE_UNKNOWN;
            $this->identifier = null;
            return;
        }

        [$type, $identifier] = explode(':', $tagString, 2);

        // Check if we have a valid type and non-empty identifier
        if (in_array($type, [self::TYPE_ENTRY, self::TYPE_SECTION]) && !empty($identifier)) {
            $this->type = $type;
            $this->identifier = $identifier;
        } else {
            $this->type = self::TYPE_UNKNOWN;
            $this->identifier = null;
        }
    }

    /**
     * String representation of the tag
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString() ?? '';
    }
}
