<?php

namespace honchoagency\craftcriticalcssgenerator\models\api;

use Craft;
use craft\base\Model;

/**
 * Critical Css Dot Com Results Response model
 */
class CriticalCssDotComResultsResponse extends Model
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

    static function createFromResponse(array $response)
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
        return $model;
    }
}
