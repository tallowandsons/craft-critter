<?php

namespace mijewe\critter\drivers\apis;

use Craft;
use craft\helpers\App;
use mijewe\critter\Critter;
use mijewe\critter\models\api\CriticalCssDotComGenerateResponse;
use mijewe\critter\models\api\CriticalCssDotComResultsResponse;
use mijewe\critter\models\UrlModel;

class CriticalCssDotComApi extends BaseRestApi
{

    // the base URI for the criticalcss.com API
    const API_BASE_URI = 'https://criticalcss.com/api/premium/';

    // default viewport dimensions for critical CSS generation
    const DEFAULT_WIDTH = 1400;
    const DEFAULT_HEIGHT = 1080;

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

    public function generate(UrlModel $urlModel, int $width = self::DEFAULT_WIDTH, int $height = self::DEFAULT_HEIGHT): ?CriticalCssDotComGenerateResponse
    {
        $data = [
            'url' => $urlModel->getAbsoluteUrl(),
            'width' => $width,
            'height' => $height,
        ];

        $response = $this->post('generate', $data);

        if ($response->isSuccess()) {
            return CriticalCssDotComGenerateResponse::createFromResponse($response->getData());
        }

        return null;
    }

    public function getResults(string $id): ?CriticalCssDotComResultsResponse
    {
        $response = $this->get('results', [
            'query' => [
                'resultId' => $id,
            ],
        ]);

        if ($response->isSuccess()) {
            return CriticalCssDotComResultsResponse::createFromResponse($response->getData());
        }

        return null;
    }
}
