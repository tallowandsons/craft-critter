<?php

namespace mijewe\critter\services;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\jobs\ExpireAllJob;
use mijewe\critter\jobs\RegenerateExpiredJob;
use mijewe\critter\models\UtilityActionResponse;
use yii\base\Component;

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
}
