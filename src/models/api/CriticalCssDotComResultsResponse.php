<?php

namespace mijewe\critter\models\api;

use Craft;
use mijewe\critter\models\BaseResponse;
use mijewe\critter\models\ApiError;

/**
 * Critical Css Dot Com Results Response model
 */
class CriticalCssDotComResultsResponse extends BaseResponse
{
    public $insertId;
    public $url;
    public $width;
    public $height;
    public $css;
    public $size;
    public $originalSize;
    public $resultStatus;
    public $createdAt;
    public $validationStatus;
    public $imageId;
    public $forceInclude;
    public $status;
    public $id;

    private ?ApiError $error = null;

    public function isDone(): bool
    {
        return $this->status === 'JOB_DONE';
    }

    public function hasCss(): bool
    {
        return !empty($this->getCss());
    }

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function getResultStatus(): ?string
    {
        return $this->resultStatus;
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
        $model->insertId = $response['insertId'] ?? null;
        $model->url = $response['url'] ?? null;
        $model->width = $response['width'] ?? null;
        $model->height = $response['height'] ?? null;
        $model->css = $response['css'] ?? null;
        $model->size = $response['size'] ?? null;
        $model->originalSize = $response['originalSize'] ?? null;
        $model->resultStatus = $response['resultStatus'] ?? null;
        $model->createdAt = $response['createdAt'] ?? null;
        $model->validationStatus = $response['validationStatus'] ?? null;
        $model->imageId = $response['imageId'] ?? null;
        $model->forceInclude = $response['forceInclude'] ?? null;
        $model->status = $response['status'] ?? null;
        $model->id = $response['id'] ?? null;
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
