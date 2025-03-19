<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;

/**
 * Api Error model
 */
class ApiError extends Model
{

    const DEFAULT_SOURCE = 'API';

    private $code = '';
    private $message = '';
    private $source;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode($code): ApiError
    {
        $this->code = $code;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage($message): ApiError
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Returns a string representation of this error.
     */
    public function toString()
    {
        $str = '';

        if (!empty($this->source)) {
            $str .= $this->source . ' says ';
        }

        // if code
        if (!empty($this->code)) {
            $str .= $this->code . ': ';
        }

        // if message
        if (!empty($this->message)) {
            $str .= $this->message;
        }


        return $str;
    }

    static public function createFromResponseContents($contents): ApiError
    {
        $errorCode = $contents[0]['errorCode'] ?? null;
        $type = $contents['Type'] ?? null;
        $message = $contents[0]['message'] ?? $contents['Message'] ?? null;

        if ($errorCode) {
            return new ApiError($errorCode, $message);
        }

        return new ApiError($type, $message);
    }
}
