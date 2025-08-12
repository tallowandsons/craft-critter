<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\elements\Entry;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\jobs\ExpireAllJob;
use tallowandsons\critter\jobs\GenerateFallbackCssJob;
use tallowandsons\critter\jobs\RegenerateAllJob;
use tallowandsons\critter\jobs\RegenerateEntryJob;
use tallowandsons\critter\jobs\RegenerateExpiredJob;
use tallowandsons\critter\jobs\RegenerateSectionJob;
use tallowandsons\critter\models\UtilityActionResponse;
use tallowandsons\critter\records\RequestRecord;
use yii\base\Component;
use DateTime;
use tallowandsons\critter\helpers\CompatibilityHelper;

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
            $section = CompatibilityHelper::getSectionByHandle($sectionHandle);
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
            // Get count of all records to report to user
            $totalRecords = RequestRecord::find()->count();

            if ($totalRecords === 0) {
                return (new UtilityActionResponse())
                    ->setSuccess(true)
                    ->setMessage(Critter::translate('No CSS records found to regenerate.'))
                    ->setData([
                        'count' => 0
                    ]);
            }

            // Queue regeneration job for all records
            $jobId = Craft::$app->getQueue()->push(new RegenerateAllJob());

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Queued regeneration job (Job ID {jobId}) for {count} CSS records.', [
                    'jobId' => $jobId,
                    'count' => $totalRecords
                ]))
                ->setData([
                    'jobId' => $jobId,
                    'count' => $totalRecords
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
            $section = CompatibilityHelper::getSectionByHandle($sectionHandle);
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
     * Generate fallback CSS by queueing jobs for each site
     */
    public function generateFallbackCss(array $siteIds = []): UtilityActionResponse
    {
        try {
            // If no site IDs provided, use all available sites
            if (empty($siteIds)) {
                $siteIds = array_map(function ($site) {
                    return $site->id;
                }, Craft::$app->getSites()->getAllSites());
            }

            // Validate all site IDs exist
            $validSiteIds = [];
            $allSites = Craft::$app->getSites()->getAllSites();
            $allSiteIds = array_map(function ($site) {
                return $site->id;
            }, $allSites);

            foreach ($siteIds as $siteId) {
                if (in_array($siteId, $allSiteIds)) {
                    $validSiteIds[] = $siteId;
                }
            }

            if (empty($validSiteIds)) {
                return (new UtilityActionResponse())
                    ->setSuccess(false)
                    ->setMessage(Critter::translate('No valid sites selected for fallback CSS generation.'));
            }

            // Queue jobs for each site (each job will look up its own fallback entry)
            $jobIds = [];
            $siteNames = [];

            foreach ($validSiteIds as $siteId) {
                $site = Craft::$app->getSites()->getSiteById($siteId);
                if ($site) {
                    $job = new GenerateFallbackCssJob([
                        'siteId' => $siteId
                    ]);

                    $jobId = Craft::$app->getQueue()->push($job);
                    $jobIds[] = $jobId;
                    $siteNames[] = $site->name;
                }
            }

            $siteNamesList = implode(', ', $siteNames);
            $jobIdsList = implode(', ', $jobIds);

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate('Queued {count} job(s) (Job IDs: {jobIds}) to generate fallback CSS for sites: {sites}.', [
                    'count' => count($jobIds),
                    'jobIds' => $jobIdsList,
                    'sites' => $siteNamesList
                ]))
                ->setData([
                    'jobIds' => $jobIds,
                    'siteIds' => $validSiteIds,
                    'siteNames' => $siteNames
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to queue fallback CSS generation: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Clear generated fallback CSS for specified sites
     */
    public function clearGeneratedFallbackCss(array $siteIds = []): UtilityActionResponse
    {
        try {
            $clearedFiles = [];
            $skippedSites = [];

            foreach ($siteIds as $siteId) {
                $site = Craft::$app->getSites()->getSiteById($siteId);
                if (!$site) {
                    continue;
                }

                if (Critter::getInstance()->fallbackService->hasGeneratedFallbackCss($site)) {
                    if (Critter::getInstance()->fallbackService->clearGeneratedFallbackCss($site)) {
                        $clearedFiles[] = $site->name;
                    }
                } else {
                    $skippedSites[] = $site->name;
                }
            }

            // Build response message
            $messages = [];
            if (!empty($clearedFiles)) {
                $messages[] = 'Cleared fallback CSS for sites: ' . implode(', ', $clearedFiles);
            }
            if (!empty($skippedSites)) {
                $messages[] = 'No fallback CSS found for sites: ' . implode(', ', $skippedSites);
            }

            $message = !empty($messages) ? implode('. ', $messages) : 'No fallback CSS files found to clear';

            return (new UtilityActionResponse())
                ->setSuccess(true)
                ->setMessage(Critter::translate($message))
                ->setData([
                    'clearedSites' => $clearedFiles,
                    'skippedSites' => $skippedSites,
                    'siteIds' => $siteIds
                ]);
        } catch (\Exception $e) {
            return (new UtilityActionResponse())
                ->setSuccess(false)
                ->setMessage(Critter::translate('Failed to clear generated fallback CSS: {error}', [
                    'error' => $e->getMessage()
                ]));
        }
    }
}
