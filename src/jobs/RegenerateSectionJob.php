<?php

namespace tallowandsons\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\records\RequestRecord;

/**
 * Regenerate Section Job queue job
 */
class RegenerateSectionJob extends BaseJob
{
    public string $sectionHandle;

    function execute($queue): void
    {
        // Find records with the section:x tag
        $sectionTag = "section:{$this->sectionHandle}";
        $records = RequestRecord::find()
            ->where(['like', 'tag', $sectionTag])
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
            $this->setProgress($queue, $index / $totalRecords, Critter::translate('Regenerating CSS record {current} of {total} for section {sectionHandle}', [
                'current' => $index + 1,
                'total' => $totalRecords,
                'sectionHandle' => $this->sectionHandle
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
            Critter::warning("Regenerated {$regenerated} CSS records for section {$this->sectionHandle}, {$failed} failed", __METHOD__);
        } else {
            Critter::info("Successfully regenerated {$regenerated} CSS records for section {$this->sectionHandle}", __METHOD__);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Regenerating CSS records for section {sectionHandle}', ['sectionHandle' => $this->sectionHandle]);
    }
}
