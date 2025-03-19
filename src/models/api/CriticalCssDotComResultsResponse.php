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

    public function getCss(): string
    {
        return $this->css;
    }

    static function createFromResponse(array $response)
    {
        $model = new self();
        $model->insertId = $response['insertId'];
        $model->url = $response['url'];
        $model->width = $response['width'];
        $model->height = $response['height'];
        $model->css = $response['css'];
        $model->size = $response['size'];
        $model->originalSize = $response['originalSize'];
        $model->resultStatus = $response['resultStatus'];
        $model->createdAt = $response['createdAt'];
        $model->validationStatus = $response['validationStatus'];
        $model->imageId = $response['imageId'];
        $model->forceInclude = $response['forceInclude'];
        $model->status = $response['status'];
        $model->id = $response['id'];
        return $model;
    }
}
