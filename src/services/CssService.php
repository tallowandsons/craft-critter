<?php

namespace mijewe\critter\services;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\factories\UrlFactory;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use yii\base\Component;

/**
 * Css service
 */
class CssService extends Component
{
    public $useQueue = true;
    public $fallbackCss = "";

    public function renderCss(): void
    {
        if ($this->isCssableRequest()) {
            $css = $this->getCssForRequest();
            Craft::$app->getView()->registerCss($css, $this->formatTagAttributes(Critter::getInstance()->settings->styleTagAttributes));
        }
    }

    public function getCssForRequest(bool $generate = true): ?string
    {
        $url = UrlFactory::createFromRequest(Craft::$app->getRequest());
        return $this->getCssForUrl($url, $generate);
    }

    public function getCssForUrl(UrlModel $url, bool $generate = true): ?string
    {
        $cssRequest = (new CssRequest())->setRequestUrl($url);

        // create a record for the URL if it doesn't exist
        Critter::getInstance()->requestRecords->createRecordIfNotExists($cssRequest);

        // return css from storage if it exists
        $cssModel = Critter::getInstance()->storage->get($cssRequest);
        if (!$cssModel->isEmpty()) {
            return $cssModel->getCss();
        }

        // if there is no css in storage, start generating new css and return fallback css
        if ($generate) {
            Critter::getInstance()->generator->startGenerate($cssRequest, $this->useQueue);
        }

        return (new CssModel($this->fallbackCss))->getCss();
    }

    /**
     * Determine if the current request is eligible for CSS generation.
     */
    public function isCssableRequest(): bool
    {
        $request = Craft::$app->getRequest();

        // only generate Critical CSS for site requests that are GET requests
        // and not console requests, preview requests, action requests, or non-HTML requests
        // This is to prevent generating CSS for admin requests, API requests, assets requests, etc.
        if (
            !$request->getIsSiteRequest() ||
            !$request->getIsGet() ||
            $request->getIsConsoleRequest() ||
            $request->getIsPreview() ||
            $request->getIsActionRequest() ||
            !$request->accepts('text/html')
        ) {
            return false;
        }

        // only generate Critical CSS if the site is live
        if (!Craft::$app->getIsLive()) {
            return false;
        }

        // only generate Critical CSS if the response is OK
        // This is to prevent generating CSS for 404 or other error pages
        $response = Craft::$app->getResponse();
        if (($response && !$response->getIsOk())) {
            return false;
        }

        return true;
    }

    /**
     * Format styleTagAttributes for the style tag.
     */
    private function formatTagAttributes(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $attr) {
            if (isset($attr['enabled']) && $attr['enabled'] && isset($attr['key']) && isset($attr['value'])) {
                $result[$attr['key']] = $attr['value'];
            }
        }
        return $result;
    }
}
