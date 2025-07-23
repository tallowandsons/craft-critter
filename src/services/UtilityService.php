<?php

namespace mijewe\critter\services;

use Craft;
use craft\elements\Entry;
use mijewe\critter\Critter;
use mijewe\critter\jobs\ExpireAllJob;
use mijewe\critter\jobs\RegenerateExpiredJob;
use mijewe\critter\models\UtilityActionResponse;
use mijewe\critter\records\RequestRecord;
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
}
