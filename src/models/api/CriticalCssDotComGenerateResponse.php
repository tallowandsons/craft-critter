<?php

namespace honchoagency\craftcriticalcssgenerator\models\api;

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
        return $this->job['id'];
    }

    public function getJobStatus()
    {
        return $this->job['status'];
    }

    public function getJobError()
    {
        return $this->job['error'];
    }

    static function createFromResponse(array $response)
    {
        $model = new self();
        $model->job = $response['job'];
        return $model;
    }
}
