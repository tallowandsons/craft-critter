<?php

namespace mijewe\critter\models\api;

use Craft;
use mijewe\critter\models\BaseResponse;
use mijewe\critter\models\ApiError;

/**
 * Critical Css Dot Com Generate Response model
 */
class CriticalCssDotComGenerateResponse extends BaseResponse
{
    public ?array $job = null;
    private ?ApiError $error = null;

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

    public function setError(?ApiError $error): self
    {
        $this->error = $error;
        $this->setSuccess(false);
        return $this;
    }

    public function getError(): ?ApiError
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    static function createFromResponse(array $response): self
    {
        $model = new self();
        $model->job = $response['job'] ?? null;
        $model->setSuccess(true);
        return $model;
    }

    static function createWithError(ApiError $error): self
    {
        $model = new self();
        $model->setError($error);
        return $model;
    }
}
