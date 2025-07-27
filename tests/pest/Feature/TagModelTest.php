<?php

use mijewe\critter\models\Tag;

describe('Tag Model Tests', function () {

    describe('Tag Creation', function () {

        it('creates entry tags correctly', function () {
            $tag = Tag::fromString('entry:123');

            expect($tag->getType())->toBe(Tag::TYPE_ENTRY);
            expect($tag->getIdentifier())->toBe('123');
            expect($tag->isEntry())->toBeTrue();
            expect($tag->isSection())->toBeFalse();
            expect($tag->isValid())->toBeTrue();
            expect($tag->toString())->toBe('entry:123');
        });

        it('creates section tags correctly', function () {
            $tag = Tag::fromString('section:blog');

            expect($tag->getType())->toBe(Tag::TYPE_SECTION);
            expect($tag->getIdentifier())->toBe('blog');
            expect($tag->isEntry())->toBeFalse();
            expect($tag->isSection())->toBeTrue();
            expect($tag->isValid())->toBeTrue();
            expect($tag->toString())->toBe('section:blog');
        });

        it('creates entry tags from Entry objects', function () {
            // Create a mock entry with ID 456
            $entry = new \craft\elements\Entry();
            $entry->id = 456;

            $tag = Tag::fromEntry($entry);

            expect($tag->getType())->toBe(Tag::TYPE_ENTRY);
            expect($tag->getIdentifier())->toBe('456');
            expect($tag->isEntry())->toBeTrue();
            expect($tag->isSection())->toBeFalse();
            expect($tag->isValid())->toBeTrue();
            expect($tag->toString())->toBe('entry:456');
        });

        it('handles unknown tag types', function () {
            $tag = Tag::fromString('unknown:something');

            expect($tag->getType())->toBe(Tag::TYPE_UNKNOWN);
            expect($tag->getIdentifier())->toBeNull();
            expect($tag->isEntry())->toBeFalse();
            expect($tag->isSection())->toBeFalse();
            expect($tag->isValid())->toBeFalse();
            expect($tag->toString())->toBeNull();
        });

        it('handles malformed tag strings', function () {
            $malformedTags = [
                '',
                'nocolon',
                'entry:',
                ':123',
                null
            ];

            foreach ($malformedTags as $tagString) {
                $tag = Tag::fromString($tagString);

                expect($tag->getType())->toBe(Tag::TYPE_UNKNOWN);
                expect($tag->getIdentifier())->toBeNull();
                expect($tag->isValid())->toBeFalse();
                expect($tag->toString())->toBeNull();
            }
        });
    });

    describe('Entry Methods', function () {

        it('returns null for entry when not an entry tag', function () {
            $tag = Tag::fromString('section:blog');

            expect($tag->getEntry(1))->toBeNull();
        });

        it('returns null for entry with invalid ID', function () {
            $invalidIds = ['entry:0', 'entry:-1', 'entry:abc'];

            foreach ($invalidIds as $tagString) {
                $tag = Tag::fromString($tagString);

                expect($tag->getEntry(1))->toBeNull();
            }
        });

        it('attempts to find entry for valid entry tags', function () {
            $tag = Tag::fromString('entry:999999'); // Non-existent entry

            // Should return null for non-existent entry, but shouldn't error
            expect($tag->getEntry(1))->toBeNull();
        });
    });

    describe('Section Methods', function () {

        it('returns null for section when not a section tag', function () {
            $tag = Tag::fromString('entry:123');

            expect($tag->getSection())->toBeNull();
        });

        it('attempts to find section for valid section tags', function () {
            $tag = Tag::fromString('section:nonexistent');

            // Should return null for non-existent section, but shouldn't error
            expect($tag->getSection())->toBeNull();
        });
    });

    describe('String Conversion', function () {

        it('converts to string correctly', function () {
            $tag = Tag::fromString('entry:123');

            expect((string) $tag)->toBe('entry:123');
        });

        it('converts unknown tags to empty string', function () {
            $tag = Tag::fromString('invalid');

            expect((string) $tag)->toBe('');
        });
    });

    describe('Edge Cases', function () {

        it('handles tags with multiple colons', function () {
            $tag = Tag::fromString('entry:123:extra');

            expect($tag->getType())->toBe(Tag::TYPE_ENTRY);
            expect($tag->getIdentifier())->toBe('123:extra'); // Should preserve everything after first colon
            expect($tag->isValid())->toBeTrue();
        });

        it('handles empty constructor', function () {
            $tag = new Tag();

            expect($tag->getType())->toBe(Tag::TYPE_UNKNOWN);
            expect($tag->isValid())->toBeFalse();
        });
    });
});
