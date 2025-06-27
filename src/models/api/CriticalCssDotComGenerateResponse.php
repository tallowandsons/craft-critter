<?php

namespace mijewe\craftcriticalcssgenerator\models\api;

use Craft;
use craft\base\Model;

/**
 * Critical Css Dot Com Generate Response model
 */
class CriticalCssDotComGenerateResponse extends Model
{

    public array $job;

    public function getJobId()
    {
        return $this->job['id'] ?? null;
    }

    public function getJobStatus()
    {
        return $this->job['status'] ?? null;
    }

    public function getJobError()
    {
        return $this->job['error'] ?? null;
    }

    static function createFromResponse(array $response)
    {
        $model = new self();
        $model->job = $response['job'] ?? null;
        return $model;
    }
}
