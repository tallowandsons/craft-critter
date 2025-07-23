<?php

namespace mijewe\critter\jobs;

use Craft;
use craft\queue\BaseJob;
use DateTime;
use mijewe\critter\Critter;
use mijewe\critter\records\RequestRecord;

/**
 * Expire All Job queue job
 */
class ExpireAllJob extends BaseJob
{
    function execute($queue): void
    {

        // get all records that are not already expired
        $now = new DateTime();
        $records = RequestRecord::find()
            ->where([
                'or',
                ['expiryDate' => null],
                ['>', 'expiryDate', $now->format('Y-m-d H:i:s')]
            ])
            ->all();

        // if no records found, exit early
        $totalRecords = count($records);
        if ($totalRecords === 0) {
            return;
        }

        // loop through each record and set expiry date to NOW
        $expired = 0;
        foreach ($records as $record) {

            // Update queue job progress
            $this->setProgress($queue, ++$expired / $totalRecords, "Expiring record {$expired}/{$totalRecords}");

            $record->expiryDate = new DateTime();
            if (!$record->save()) {
                Critter::getInstance()->log->error("Failed to expire record {$record->id}: " . implode(', ', $record->getErrorSummary(true)), 'expiration');
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Critter::translate('Expiring all CSS records');
    }
}
