<?php

namespace mijewe\craftcriticalcssgenerator\drivers\apis;

use Craft;
use craft\helpers\App;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\models\api\CriticalCssDotComGenerateResponse;
use mijewe\craftcriticalcssgenerator\models\api\CriticalCssDotComResultsResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

class CriticalCssDotComApi extends BaseRestApi
{

    // the base URI for the criticalcss.com API
    const API_BASE_URI = 'https://criticalcss.com/api/premium/';

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

    public function generate(UrlModel $urlModel): ?CriticalCssDotComGenerateResponse
    {
        $response = $this->post('generate', [
            'url' => $urlModel->getAbsoluteUrl(),
        ]);

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
