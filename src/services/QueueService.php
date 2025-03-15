<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use craft\queue\BaseJob;
use honchoagency\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use yii\base\Component;

/**
 * Queue Service service
 */
class QueueService extends Component
{

    public function pushIfNew(BaseJob $job): void
    {
        if (!$this->isInQueue($job)) {
            Craft::$app->queue->push($job);
        } else {
            Craft::info("Critical CSS generation job already in queue for '" . $job->getDescription() . "'", "critical-css-generator");
        }
    }

    public function isInQueue(BaseJob $job): bool
    {
        $jobDescription = $job->getDescription();

        $jobInfoArray = Craft::$app->queue->getJobInfo();

        foreach ($jobInfoArray as $jobInfo) {
            if ($jobInfo['description'] === $jobDescription) {
                return true;
            }
        }

        return false;
    }
}
