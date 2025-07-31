<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\elements\Entry;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\jobs\ExpireAllJob;
use tallowandsons\critter\jobs\RegenerateAllJob;
use tallowandsons\critter\jobs\RegenerateEntryJob;
use tallowandsons\critter\jobs\RegenerateExpiredJob;
use tallowandsons\critter\jobs\RegenerateSectionJob;
use tallowandsons\critter\models\UtilityActionResponse;
use tallowandsons\critter\records\RequestRecord;
use yii\base\Component;
use DateTime;

/**
 * Utility Service service
 */
class UtilityService extends Component
{
    /**
     * Expire all cached Critical CSS records
     * This will queue a job to expire all CSS records
     * and return a response indicating success or failure.
     */
    public function expireAll()
    {
        $jobId = Craft::$app->getQueue()->push(new ExpireAllJob());

        return (new UtilityActionResponse())
            ->setSuccess(true)
            ->setMessage(Critter::translate('Queued job (Job ID {id}) - All CSS records will be expired.', ['id' => $jobId]))
            ->setData([
                'jobId' => $jobId,
            ]);
    }

    /**
     * Regenerate all expired CSS records
     * This will queue a job to regenerate all expired CSS records
     * and return a response indicating success or failure.
     */
    public function regenerateExpired()
    {
        $jobId = Craft::$app->getQueue()->push(new RegenerateExpiredJob());

        return (new UtilityActionResponse())
            ->setSuccess(true)
            ->setMessage(Critter::translate('Queued job (Job ID {id}) - Expired CSS records will be regenerated.', ['id' => $jobId]))
            ->setData([
                'jobId' => $jobId
            ]);
    }

    /**
     * Regenerate CSS records for a specific entry by entry ID
     * This will regenerate CSS for all records with the entry:x tag regardless of expiration status
     */
    public function regenerateEntry(int $entryId)
    {
        try {
            // Find entry to validate it exists
            $entry = Entry::find()->id($entryId)->one();
            if (!$entry) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Entry with ID {id} not found.', ['id' => $entryId]));
            }

            // Queue regeneration job for this specific entry
            $job = new RegenerateEntryJob();
            $job->entryId = $entryId;
            $jobId = Craft::$app->getQueue()->push($job);

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Queued regeneration job (Job ID {jobId}) for entry "{title}" (ID: {id}).', [
                    'jobId' => $jobId,
                    'title' => $entry->title,
                    'id' => $entryId
                ]))
                ->setData([
                    'jobId' => $jobId,
                    'entryId' => $entryId,
                    'entryTitle' => $entry->title
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to regenerate CSS for entry ID {id}: {error}', [
                    'id' => $entryId,
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Regenerate CSS records for a specific section by section handle
     * This will regenerate CSS for all records with the section:x tag regardless of expiration status
     */
    public function regenerateSection(string $sectionHandle)
    {
        try {
            // Find section to validate it exists
            $section = Craft::$app->entries->getSectionByHandle($sectionHandle);
            if (!$section) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Section with handle "{handle}" not found.', ['handle' => $sectionHandle]));
            }

            // Queue regeneration job for this specific section
            $job = new RegenerateSectionJob();
            $job->sectionHandle = $sectionHandle;
            $jobId = Craft::$app->getQueue()->push($job);

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Queued regeneration job (Job ID {jobId}) for section "{name}" (handle: {handle}).', [
                    'jobId' => $jobId,
                    'name' => $section->name,
                    'handle' => $sectionHandle
                ]))
                ->setData([
                    'jobId' => $jobId,
                    'sectionId' => $section->id,
                    'sectionHandle' => $sectionHandle,
                    'sectionName' => $section->name
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to regenerate CSS for section handle "{handle}": {error}', [
                    'handle' => $sectionHandle,
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Regenerate all CSS records (not just expired ones)
     * This will regenerate all CSS records regardless of expiration status
     */
    public function regenerateAll()
    {
        try {
            // Queue regeneration job for all records
            $jobId = Craft::$app->getQueue()->push(new RegenerateAllJob());

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Queued regeneration job (Job ID {jobId}) for all CSS records.', [
                    'jobId' => $jobId
                ]))
                ->setData([
                    'jobId' => $jobId
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to regenerate all CSS: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Expire CSS records for a specific entry by entry ID
     * This will find all records with the entry:x tag and expire them
     */
    public function expireEntry(int $entryId)
    {
        try {
            // Find entry to validate it exists
            $entry = Entry::find()->id($entryId)->one();
            if (!$entry) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Entry with ID {id} not found.', ['id' => $entryId]));
            }

            // Find records with the entry:x tag
            $entryTag = "entry:{$entryId}";
            $records = RequestRecord::find()
                ->where(['like', 'tag', $entryTag])
                ->andWhere(['or', ['expiryDate' => null], ['>', 'expiryDate', (new DateTime())->format('Y-m-d H:i:s')]])
                ->all();

            if (empty($records)) {
                return (new UtilityActionResponse())
                    ->setSuccess(true)
                    ->setMessage(Critter::translate('No unexpired CSS records found for entry "{title}" (ID: {id}).', [
                        'title' => $entry->title,
                        'id' => $entryId
                    ]));
            }

            // Set expiry date to now for all matching records
            $now = new DateTime();
            $updatedCount = RequestRecord::updateAll(
                ['expiryDate' => $now->format('Y-m-d H:i:s')],
                ['like', 'tag', $entryTag]
            );

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully expired {count} CSS records for entry "{title}" (ID: {id}).', [
                    'count' => $updatedCount,
                    'title' => $entry->title,
                    'id' => $entryId
                ]))
                ->setData([
                    'count' => $updatedCount,
                    'entryId' => $entryId,
                    'entryTitle' => $entry->title
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to expire CSS for entry ID {id}: {error}', [
                    'id' => $entryId,
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Expire CSS records for a specific section by section handle
     * This will find all records with the section:x tag and expire them
     */
    public function expireSection(string $sectionHandle)
    {
        try {
            // Find section to validate it exists
            $section = Craft::$app->entries->getSectionByHandle($sectionHandle);
            if (!$section) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Section with handle "{handle}" not found.', ['handle' => $sectionHandle]));
            }

            // Find records with the section:x tag (using section handle)
            $sectionTag = "section:{$sectionHandle}";
            $records = RequestRecord::find()
                ->where(['like', 'tag', $sectionTag])
                ->andWhere(['or', ['expiryDate' => null], ['>', 'expiryDate', (new DateTime())->format('Y-m-d H:i:s')]])
                ->all();

            if (empty($records)) {
                return (new UtilityActionResponse())
                    ->setSuccess(true)
                    ->setMessage(Critter::translate('No unexpired CSS records found for section "{name}" (handle: {handle}).', [
                        'name' => $section->name,
                        'handle' => $sectionHandle
                    ]));
            }

            // Set expiry date to now for all matching records
            $now = new DateTime();
            $updatedCount = RequestRecord::updateAll(
                ['expiryDate' => $now->format('Y-m-d H:i:s')],
                ['like', 'tag', $sectionTag]
            );

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully expired {count} CSS records for section "{name}" (handle: {handle}).', [
                    'count' => $updatedCount,
                    'name' => $section->name,
                    'handle' => $sectionHandle
                ]))
                ->setData([
                    'count' => $updatedCount,
                    'sectionId' => $section->id,
                    'sectionHandle' => $sectionHandle,
                    'sectionName' => $section->name
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to expire CSS for section handle "{handle}": {error}', [
                    'handle' => $sectionHandle,
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Clear stuck CSS generation records
     * This will find and clear records that have API data but no CSS (stuck jobs)
     */
    public function clearStuckRecords()
    {
        try {
            // Find records that have API data (indicating a job was started) but are in error state
            // or have been pending for a long time
            $records = RequestRecord::find()
                ->where(['in', 'status', [RequestRecord::STATUS_ERROR, RequestRecord::STATUS_GENERATING, RequestRecord::STATUS_QUEUED]])
                ->all();

            if (empty($records)) {
                return (new UtilityActionResponse())
                    ->setSuccess(true)
                    ->setMessage(Critter::translate('No stuck CSS records found.'));
            }

            $clearedCount = 0;
            foreach ($records as $record) {
                // Clear the data and reset status to allow fresh generation
                $record->data = null;
                $record->status = RequestRecord::STATUS_TODO;
                if ($record->save()) {
                    $clearedCount++;
                }
            }

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully cleared {count} stuck CSS records.', [
                    'count' => $clearedCount
                ]))
                ->setData([
                    'count' => $clearedCount
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to clear stuck records: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Clear all Critter cache data
     * This will clear all cached CSS without affecting other cached data
     */
    public function clearCache()
    {
        try {
            Critter::getInstance()->storage->clearAll();

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully cleared Critter cache data.'));
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to clear cache: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Generate fallback CSS from an entry and save it to storage
     */
    public function generateFallbackCss(int $entryId): UtilityActionResponse
    {
        try {
            // Get the entry
            $entry = Entry::find()->id($entryId)->one();
            if (!$entry) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Entry not found with ID: {id}', ['id' => $entryId]));
            }

            // Generate CSS for the entry's URL
            $url = UrlFactory::createFromEntry($entry);
            $css = Critter::getInstance()->css->getCssForUrl($url, true);

            if (!$css) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Failed to generate CSS for entry: {title}', ['title' => $entry->title]));
            }

            // Save CSS to storage file
            $fallbackPath = $this->saveFallbackCssToStorage($css);
            if (!$fallbackPath) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('Failed to save fallback CSS to storage.'));
            }

            // Update settings to use generated fallback CSS
            $settings = Critter::getInstance()->getSettings();
            $settings->useGeneratedFallbackCss = true;
            $settings->fallbackCssEntryId = $entryId;

            // Save settings via the plugin
            $plugin = Critter::getInstance();
            Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray());

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully generated fallback CSS from entry "{title}" and saved to storage.', [
                    'title' => $entry->title
                ]));
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to generate fallback CSS: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Save fallback CSS content to storage and return the file path
     */
    private function saveFallbackCssToStorage(string $css): ?string
    {
        try {
            $storagePath = Craft::$app->getPath()->getStoragePath();
            $fallbackDir = $storagePath . DIRECTORY_SEPARATOR . 'critter';

            // Ensure directory exists
            if (!is_dir($fallbackDir)) {
                mkdir($fallbackDir, 0755, true);
            }

            $fallbackFile = $fallbackDir . DIRECTORY_SEPARATOR . 'fallback.css';

            if (file_put_contents($fallbackFile, $css) !== false) {
                return $fallbackFile;
            }
        } catch (\Exception $e) {
            Critter::error("Failed to save fallback CSS to storage: " . $e->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * Clear generated fallback CSS and disable the use of generated fallback
     */
    public function clearGeneratedFallbackCss(): UtilityActionResponse
    {
        try {
            // Remove the generated fallback CSS file
            $storagePath = Craft::$app->getPath()->getStoragePath();
            $fallbackFile = $storagePath . DIRECTORY_SEPARATOR . 'critter' . DIRECTORY_SEPARATOR . 'fallback.css';

            if (file_exists($fallbackFile)) {
                unlink($fallbackFile);
            }

            // Update settings to disable generated fallback CSS
            $settings = Critter::getInstance()->getSettings();
            $settings->useGeneratedFallbackCss = false;
            $settings->fallbackCssEntryId = null;

            // Save settings via the plugin
            $plugin = Critter::getInstance();
            Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray());

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Successfully cleared generated fallback CSS. Fallback CSS will now use the configured file path (if any).'));
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to clear generated fallback CSS: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }
}
