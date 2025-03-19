<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;

/**
 * Api Response model
 */
class ApiResponse extends BaseResponse
{

    public $statusCode = 400;
    public $message = '';
    public ?ApiError $error = null;
    protected $response;

    /**
     * Set the status code property
     * @param int $statusCode
     * @return ApiResponse
     */
    public function setStatusCode(int $statusCode): ApiResponse
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the message property
     * @param string $message
     * @return ApiResponse
     */
    public function setMessage(string $message): ApiResponse
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the error property
     * @param ApiError $error
     * @return ApiResponse
     */
    public function setError(ApiError $error): ApiResponse
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get the error property
     * @return ApiError|null
     */
    public function getError(): ?ApiError
    {
        return $this->error;
    }

    public function getResponse(): ?\Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set the response property
     * This is the raw response from the Guzzle client
     */
    public function setResponse(\Psr\Http\Message\ResponseInterface $response): ApiResponse
    {
        $this->response = $response;
        return $this;
    }
}
