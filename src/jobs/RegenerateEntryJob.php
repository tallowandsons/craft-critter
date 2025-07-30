<?php

namespace tallowandsons\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\records\RequestRecord;

/**
 * Regenerate Entry Job queue job
 */
class RegenerateEntryJob extends BaseJob
{
    public int $entryId;

    function execute($queue): void
    {
        // Find records with the entry:x tag
        $entryTag = "entry:{$this->entryId}";
        $records = RequestRecord::find()
            ->where(['like', 'tag', $entryTag])
            ->all();

        // if no records found, exit early
        $totalRecords = count($records);
        if ($totalRecords === 0) {
            return;
        }

        $regenerated = 0;
        $failed = 0;

        foreach ($records as $index => $record) {
            // Update queue job progress
            $this->setProgress($queue, $index / $totalRecords, Critter::translate('Regenerating CSS record {current} of {total} for entry {entryId}', [
                'current' => $index + 1,
                'total' => $totalRecords,
                'entryId' => $this->entryId
            ]));

            try {
                // Create URL model from record (handles entry URI changes)
                $urlModel = UrlFactory::createFromRecord($record);

                // Create CSS request
                $cssRequest = (new CssRequest())->setRequestUrl($urlModel);

                // Generate CSS directly (don't expire, just regenerate)
                Critter::getInstance()->generator->generate($cssRequest, true, true);
                $regenerated++;
            } catch (\Exception $e) {
                Critter::error("Failed to regenerate CSS for record ID {$record->id}: " . $e->getMessage(), __METHOD__);
                $failed++;
            }
        }

        // Log completion
        if ($failed > 0) {
            Critter::warning("Regenerated {$regenerated} CSS records for entry {$this->entryId}, {$failed} failed", __METHOD__);
        } else {
            Critter::info("Successfully regenerated {$regenerated} CSS records for entry {$this->entryId}", __METHOD__);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Regenerating CSS records for entry {entryId}', ['entryId' => $this->entryId]);
    }
}
