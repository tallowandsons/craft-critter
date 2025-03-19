<?php

namespace honchoagency\craftcriticalcssgenerator\drivers\apis;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use honchoagency\craftcriticalcssgenerator\exceptions\ApiException;
use honchoagency\craftcriticalcssgenerator\models\ApiError;
use honchoagency\craftcriticalcssgenerator\models\ApiResponse;

class BaseRestApi extends Component
{


    private Client $client;
    private string $baseUri;
    private bool $verifySsl = false;
    private array $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function init(): void
    {
        parent::init();

        // if (empty($this->baseUri)) {
        //     throw new \Exception("Base URI not set");
        // }

        $this->client = Craft::createGuzzleClient([
            "base_uri" => $this->baseUri,
            "verify" => $this->verifySsl,
            "headers" => $this->headers,
        ]);
    }

    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }

    public function addHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Make a GET request
     */
    public function get(string $path, array $options = [], bool $allowFailure = false): ApiResponse
    {
        $response = $this->makeRequest('GET', $path, $options);

        if (!$response->isSuccess() && !$allowFailure) {
            throw new ApiException($response->getError()->getMessage());
        }

        return $response;
    }

    /**
     * Make a POST request
     */
    public function post(string $path, array $body): ApiResponse
    {
        return $this->makeRequest('POST', $path, ['body' => Json::encode($body)]);
    }

    private function makeRequest(string $method, string $path, array $options = []): ApiResponse
    {
        $response = new ApiResponse();

        try {
            $guzzleResponse = $this->client->request($method, $path, $options);

            if (is_null($guzzleResponse)) {
                $response->setSuccess(false);
                $response->setMessage("No response from API");
                return $response;
            }

            $response->setSuccess(true);
            $response->setStatusCode($guzzleResponse->getStatusCode());
            $response->setData(json_decode($guzzleResponse->getBody()->getContents(), true));
            return $response;
        } catch (BadResponseException $e) {
            return $this->handleException($e);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }

    private function handleException($exception): ApiResponse
    {
        $response = new ApiResponse();
        $response->setSuccess(false);

        if ($exception instanceof BadResponseException) {

            $guzzleResponse = $exception->getResponse();
            $response->setSuccess(false);
            $response->setStatusCode($guzzleResponse->getStatusCode());

            try {
                // a 404 response from the API will have a JSON body with an error code and message
                $contentsJson = $guzzleResponse->getBody()->getContents();
                $contents = Json::decode($contentsJson);
                $apiError = ApiError::createFromResponseContents($contents);
                $response->setError($apiError);
            } catch (\Throwable $th) {
                $response->setError(new ApiError(null, $guzzleResponse->getReasonPhrase()));
            }

            $response->setResponse($guzzleResponse);
        } else {
            $response->setSuccess(false);
            $response->setStatusCode(500);
            $response->setError(new ApiError(null, $exception->getMessage()));
        }

        return $response;
    }
}
