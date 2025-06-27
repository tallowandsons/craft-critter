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

    const API_BASE_URI = 'https://criticalcss.com/api/premium/';

    private string $apiKey;

    // constructor
    public function __construct()
    {

        $this->setApiKeyFromConfig();

        $this->setBaseUri(self::API_BASE_URI);

        $this->addHeader('Authorization', 'JWT ' . $this->apiKey);

        parent::__construct();
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiKeyFromConfig()
    {
        $this->setApiKey(App::parseEnv(Critical::getInstance()->settings->generatorApiKey));
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
