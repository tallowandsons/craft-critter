<?php

namespace tallowandsons\critter\drivers\apis;

use Craft;
use craft\helpers\App;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\api\CriticalCssDotComGenerateResponse;
use tallowandsons\critter\models\api\CriticalCssDotComResultsResponse;
use tallowandsons\critter\models\UrlModel;

class CriticalCssDotComApi extends BaseRestApi
{

    // the base URI for the criticalcss.com API
    const API_BASE_URI = 'https://criticalcss.com/api/premium/';

    // default viewport dimensions for critical CSS generation
    const DEFAULT_WIDTH = 1400;
    const DEFAULT_HEIGHT = 1080;

    // job statuses
    const STATUS_JOB_ONGOING = 'JOB_ONGOING';
    const STATUS_JOB_QUEUED = 'JOB_QUEUED';
    const STATUS_JOB_DONE = 'JOB_DONE';
    const STATUS_JOB_UNKNOWN = 'JOB_UNKNOWN';
    const STATUS_JOB_BAD = 'STATUS_JOB_BAD';

    // result statuses
    const RESULT_STATUS_BAD_GATEWAY = 'BAD_GATEWAY';
    const RESULT_STATUS_ECONNRESET = 'ECONNRESET';
    const RESULT_STATUS_ENOTFOUND = 'ENOTFOUND';
    const RESULT_STATUS_ECONNREFUSED = 'ECONNREFUSED';
    const RESULT_STATUS_EHOSTUNREACH = 'EHOSTUNREACH';
    const RESULT_STATUS_FORBIDDEN = 'FORBIDDEN';
    const RESULT_STATUS_HOST_NOT_FOUND = 'HOST_NOT_FOUND';
    const RESULT_STATUS_INVALID_PROTOCOL = 'INVALID_PROTOCOL';
    const RESULT_STATUS_INVALID_HOST_CERT = 'INVALID_HOST_CERT';
    const RESULT_STATUS_SERVER_ERROR = 'SERVER_ERROR';
    const RESULT_STATUS_SSL_ERROR = 'SSL_ERROR';
    const RESULT_STATUS_UNAUTHORIZED = 'UNAUTHORIZED';
    const RESULT_STATUS_CSS_REQUEST_ERROR = 'CSS_REQUEST_ERROR';
    const RESULT_STATUS_CSS_TIMEOUT = 'CSS_TIMEOUT';
    const RESULT_STATUS_FAILED_FETCHING_CSS = 'FAILED_FETCHING_CSS';
    const RESULT_STATUS_HTML_404 = 'HTML_404';
    const RESULT_STATUS_HTML_TIMEOUT = 'HTML_TIMEOUT';
    const RESULT_STATUS_HTML_EMPTY = 'HTML_EMPTY';
    const RESULT_STATUS_HTML_SCRAPING_BLOCKED = 'HTML_SCRAPING_BLOCKED';
    const RESULT_STATUS_HTML_INVALID_WORDPRESS_REDIRECTS = 'HTML_INVALID_WORDPRESS_REDIRECTS';
    const RESULT_STATUS_CRITICAL_CSS_EMPTY = 'CRITICAL_CSS_EMPTY';
    const RESULT_STATUS_PENTHOUSE_TIMEOUT = 'PENTHOUSE_TIMEOUT';
    const RESULT_STATUS_HTTP_SOCKET_HANG_UP = 'HTTP_SOCKET_HANG_UP';
    const RESULT_STATUS_WORKER_TIMEOUT = 'WORKER_TIMEOUT';

    // the API key for the criticalcss.com account
    private string $apiKey;

    // constructor
    public function __construct(?string $apiKey = null)
    {
        $this->setBaseUri(self::API_BASE_URI);

        if ($apiKey) {
            $this->setApiKey($apiKey);
        }

        parent::__construct();
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        $this->addHeader('Authorization', 'JWT ' . $this->apiKey);
        return $this;
    }

    /**
     * Check if an API error is an authentication error when API key is present
     */
    private function isAuthError($error): bool
    {
        return isset($this->apiKey) && $error && $error->getCode() === 'INVALID_JWT_TOKEN';
    }

    /**
     * Handle authentication errors by throwing an exception
     */
    private function handleAuthError($error): void
    {
        if ($this->isAuthError($error)) {
            throw new \Exception('criticalcss.com API authentication failed: ' . $error->getMessage());
        }
    }

    public function generate(UrlModel $urlModel, int $width = self::DEFAULT_WIDTH, int $height = self::DEFAULT_HEIGHT): CriticalCssDotComGenerateResponse
    {
        // Build optional Basic Auth header from settings
        $settings = Critter::getInstance()->getSettings();

        $username = $settings->basicAuthUsername ? App::parseEnv($settings->basicAuthUsername) : null;
        $password = $settings->basicAuthPassword ? App::parseEnv($settings->basicAuthPassword) : null;

        $customHeaders = null;
        if ($username !== null && $username !== '' && $password !== null && $password !== '') {
            $authString = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
            $customHeaders = $authString;
        }

        $data = [
            'url' => $urlModel->getAbsoluteUrl(),
            'width' => $width,
            'height' => $height,
        ];

        // Only send custom headers if configured
        if ($customHeaders) {
            $data['customPageHeaders'] = $customHeaders;
        }

        $response = $this->post('generate', $data);

        if ($response->isSuccess()) {
            return CriticalCssDotComGenerateResponse::createFromResponse($response->getData());
        }

        // Check for authentication error when API key is present
        $error = $response->getError();
        $this->handleAuthError($error);

        // if not successful, create a response with the error
        return CriticalCssDotComGenerateResponse::createWithError($error);
    }

    public function getResults(string $id): CriticalCssDotComResultsResponse
    {
        $response = $this->get('results', [
            'query' => [
                'resultId' => $id,
            ],
        ]);

        if ($response->isSuccess()) {
            return CriticalCssDotComResultsResponse::createFromResponse($response->getData());
        }

        // Check for authentication error when API key is present
        $error = $response->getError();
        $this->handleAuthError($error);

        // if not successful, create a response with the error
        return CriticalCssDotComResultsResponse::createWithError($error);
    }
}
