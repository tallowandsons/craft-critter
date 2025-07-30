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

        $regenerated = 0;
        $failed = 0;

        foreach ($records as $index => $record) {
            // Update queue job progress
            $this->setProgress($queue, $index / $totalRecords, Critter::translate('Regenerating CSS record {current} of {total}', [
                'current' => $index + 1,
                'total' => $totalRecords
            ]));

            try {
                // Create URL model from record (handles entry URI changes)
                $urlModel = UrlFactory::createFromRecord($record);

                // Create CSS request
                $cssRequest = (new CssRequest())->setRequestUrl($urlModel);

                // Generate CSS directly (don't expire, just regenerate)
                Critter::getInstance()->generator->generate($cssRequest);
                $regenerated++;
            } catch (\Exception $e) {
                Critter::error("Failed to regenerate CSS for record ID {$record->id}: " . $e->getMessage(), __METHOD__);
                $failed++;
            }
        }

        // Log completion
        if ($failed > 0) {
            Critter::warning("Regenerated {$regenerated} CSS records, {$failed} failed", __METHOD__);
        } else {
            Critter::info("Successfully regenerated {$regenerated} CSS records", __METHOD__);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Regenerating all CSS records');
    }
}
