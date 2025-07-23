<?php

namespace mijewe\critter\models;

use Craft;
use craft\base\Model;

/**
 * Utility Action Response model
 */
class UtilityActionResponse extends BaseResponse
{

    private string $message;

    public function setMessage(string $message): UtilityActionResponse
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
