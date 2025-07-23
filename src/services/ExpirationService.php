<?php

namespace mijewe\critter\services;

use Craft;
use craft\elements\Entry;
use mijewe\critter\Critter;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\Settings;
use mijewe\critter\models\UrlModel;
use yii\base\Component;

/**
 * Cache Expiration service
 * Handles expiring cached Critical CSS by updating request record expiry dates
 */
class ExpirationService extends Component
{

    /**
     * Expire Critical CSS for an entry by updating request record expiry dates
     * This marks the CSS as expired without deleting the cached data
     */
    public function expireCriticalCssForEntry(Entry $entry): bool
    {
        $success = true;

        // Log the expiration request
        Critter::getInstance()->log->info("Expiring Critical CSS for entry: {$entry->title} (ID: {$entry->id})", 'expiration');

        // determine the mode based the section settings
        $cssRequest = CssRequest::createFromEntry($entry);
        $mode = $cssRequest->getMode();

        try {
            // Determine what to expire based on the default mode
            if ($mode === Settings::MODE_ENTRY) {
                // Entry mode: expire CSS for this specific entry using relatedTag
                $entryTag = "entry:{$entry->id}";
                $success = Critter::getInstance()->requestRecords->expireRecordsByTag($entryTag);

                if ($success) {
                    Critter::getInstance()->log->debug("Expired Critical CSS records for entry tag: {$entryTag}", 'expiration');
                }
            } else {
                // Section mode: expire CSS for the entire section using relatedTag
                $section = $entry->getSection();
                if ($section) {
                    $sectionTag = "section:{$section->handle}";
                    $success = Critter::getInstance()->requestRecords->expireRecordsByTag($sectionTag);

                    if ($success) {
                        Critter::getInstance()->log->debug("Expired Critical CSS records for section tag: {$sectionTag}", 'expiration');
                    }
                } else {
                    $success = false;
                }
            }

            if ($success) {
                Critter::getInstance()->log->info("Successfully expired Critical CSS for entry: {$entry->title}", 'expiration');
            } else {
                Critter::getInstance()->log->error("Failed to expire Critical CSS for entry: {$entry->title}", 'expiration');
            }
        } catch (\Exception $e) {
            Critter::getInstance()->log->error("Exception while expiring Critical CSS for entry {$entry->id}: " . $e->getMessage(), 'expiration');
            $success = false;
        }

        return $success;
    }
}
