<?php

namespace mijewe\critter\services;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\jobs\ExpireAllJob;
use mijewe\critter\models\UtilityActionResponse;
use yii\base\Component;

/**
 * Utility Service service
 */
class UtilityService extends Component
{
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
}
