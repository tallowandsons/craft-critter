<?php

namespace mijewe\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use DateTime;
use mijewe\critter\Critter;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;

/**
 * Regenerate Expired Job queue job
 */
class RegenerateExpiredJob extends BaseJob
{
    function execute($queue): void
    {
        // Get all expired records
        $now = new DateTime();
        $records = RequestRecord::find()
            ->where([
                'and',
                ['not', ['expiryDate' => null]],
                ['<', 'expiryDate', $now->format('Y-m-d H:i:s')]
            ])
            ->all();

        // if no records found, exit early
        $totalRecords = count($records);
        if ($totalRecords === 0) {
            Critter::info('No expired CSS records found to regenerate.', 'regeneration');
            return;
        }

        $regenerated = 0;
        $failed = 0;

        foreach ($records as $index => $record) {
            // Update progress
            $this->setProgress($queue, $index / $totalRecords, "Regenerating CSS for {$record->uri}");

            try {
                // Create CSS request from record
                $url = new UrlModel($record->uri, $record->siteId);
                $cssRequest = (new CssRequest())->setRequestUrl($url);

                // Optionally trigger immediate generation
                Critter::getInstance()->generator->startGenerate($cssRequest);

                $regenerated++;
            } catch (\Exception $e) {
                Craft::error("Failed to regenerate CSS for {$record->uri}: " . $e->getMessage(), __METHOD__);
                $failed++;
            }
        }

        // Log completion
        if ($failed > 0) {
            Critter::warning("Regenerated {$regenerated} CSS records, {$failed} failed", __METHOD__);
        } else {
            Critter::info("Successfully regenerated {$regenerated} expired CSS records", __METHOD__);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Regenerating expired critical CSS records');
    }
}
