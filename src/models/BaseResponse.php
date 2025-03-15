<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use craft\base\Model;

/**
 * Storage Response model
 */
class BaseResponse extends Model
{
    public bool $success = false;
    public mixed $data;

    public function setSuccess(bool $success): BaseResponse
    {
        $this->success = $success;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set the data property
     */
    public function setData($data): BaseResponse
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the data property
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
