<?php

namespace mijewe\critter\models;

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

    /**
     * Constructor
     */
    public function __construct($code = null, $message = null, $config = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->source = self::DEFAULT_SOURCE;

        parent::__construct($config);
    }

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
        $errorCode = $contents['errorCode'] ?? null;
        $message = $contents['error'] ?? null;

        return new ApiError($errorCode, $message);
    }
}
