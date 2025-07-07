<?php

namespace mijewe\critter\console\controllers;

use Craft;
use craft\console\Controller;
use mijewe\critter\Critter;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;
use yii\console\ExitCode;

/**
 * Generate controller
 */
class GenerateController extends Controller
{
    public $defaultAction = 'index';

    /**
     * Force regeneration of critical CSS even if already complete
     */
    public bool $force = false;

    /**
     * Filter by status (todo, complete, error, queued, generating)
     */
    public ?string $status = null;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
            case 'all':
                $options[] = 'force';
                $options[] = 'status';
                break;
        }
        return $options;
    }

    /**
     * critter/generate command
     */
    public function actionIndex(): int
    {
        // ...
        return ExitCode::OK;
    }

    /**
     * Generate critical CSS for all RequestRecords
     *
     * @return int
     */
    public function actionAll(): int
    {
        $this->stdout("Finding RequestRecords...\n");

        // Build query for RequestRecords
        $query = RequestRecord::find();

        // Filter by status if specified
        if ($this->status) {
            $validStatuses = [
                RequestRecord::STATUS_TODO,
                RequestRecord::STATUS_COMPLETE,
                RequestRecord::STATUS_ERROR,
                RequestRecord::STATUS_QUEUED,
                RequestRecord::STATUS_GENERATING,
                RequestRecord::STATUS_PENDING
            ];

            if (!in_array($this->status, $validStatuses)) {
                $this->stderr("Invalid status '{$this->status}'. Valid options: " . implode(', ', $validStatuses) . "\n");
                return ExitCode::USAGE;
            }

            $query->where(['status' => $this->status]);
            $this->stdout("Filtering by status: {$this->status}\n");
        }

        // Get all RequestRecords from the database
        $requestRecords = $query->all();

        if (empty($requestRecords)) {
            $this->stdout("No RequestRecords found.\n");
            return ExitCode::OK;
        }

        $totalRecords = count($requestRecords);
        $queuedCount = 0;
        $skippedCount = 0;

        $this->stdout("Found {$totalRecords} RequestRecords.\n");

        if ($this->force) {
            $this->stdout("Force mode enabled - will regenerate even completed records.\n");
        }

        foreach ($requestRecords as $record) {
            // Skip if already complete and not forcing
            if (!$this->force && $record->status === RequestRecord::STATUS_COMPLETE) {
                $skippedCount++;
                $this->stdout("Skipping {$record->uri} (already complete)\n");
                continue;
            }

            // Skip if already in queue
            if ($record->isInQueue()) {
                $skippedCount++;
                $this->stdout("Skipping {$record->uri} (already in queue)\n");
                continue;
            }

            try {
                // Create UrlModel from the record
                $urlModel = new UrlModel($record->uri, $record->siteId);

                // Create CssRequest
                $cssRequest = (new CssRequest())->setRequestUrl($urlModel);

                // Start generating critical CSS using the queue
                Critter::getInstance()->generator->startGenerate($cssRequest, true, true);

                $queuedCount++;
                $this->stdout("Queued generation for: {$record->uri}\n");
            } catch (\Exception $e) {
                $this->stderr("Error queuing {$record->uri}: " . $e->getMessage() . "\n");
            }
        }

        $this->stdout("\nSummary:\n");
        $this->stdout("Total records: {$totalRecords}\n");
        $this->stdout("Queued for generation: {$queuedCount}\n");
        $this->stdout("Skipped: {$skippedCount}\n");

        return ExitCode::OK;
    }
}
