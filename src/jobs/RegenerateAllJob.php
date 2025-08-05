<?php

namespace tallowandsons\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use DateTime;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\records\RequestRecord;

/**
 * Regenerate All Job queue job
 */
class RegenerateAllJob extends BaseJob
{
    function execute($queue): void
    {
        // Get all records
        $records = RequestRecord::find()->all();

        // if no records found, exit early
        $totalRecords = count($records);
        if ($totalRecords === 0) {
            return;
        }

        $queued = 0;
        $failed = 0;

        foreach ($records as $index => $record) {
            // Update queue job progress
            $this->setProgress($queue, $index / $totalRecords, Critter::translate('Queueing CSS regeneration {current} of {total}', [
                'current' => $index + 1,
                'total' => $totalRecords
            ]));

            try {
                // Create URL model from record (handles entry URI changes)
                $urlModel = UrlFactory::createFromRecord($record);

                // Create CSS request
                $cssRequest = (new CssRequest())->setRequestUrl($urlModel);

                // Start generation using the queue (spawn individual jobs)
                Critter::getInstance()->generator->startGenerate($cssRequest, true, true);
                $queued++;
            } catch (\Exception $e) {
                Critter::error("Failed to queue CSS regeneration for record ID {$record->id}: " . $e->getMessage(), __METHOD__);
                $failed++;
            }
        }

        // Log completion
        if ($failed > 0) {
            Critter::warning("Queued {$queued} CSS regeneration jobs, {$failed} failed", __METHOD__);
        } else {
            Critter::info("Successfully queued {$queued} CSS regeneration jobs", __METHOD__);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Queueing CSS regeneration jobs for all records');
    }
}
