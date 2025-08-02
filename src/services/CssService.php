<?php

namespace tallowandsons\critter\services;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\CssRequest;
use tallowandsons\critter\models\Settings;
use tallowandsons\critter\models\UrlModel;
use yii\base\Component;

/**
 * Css service
 */
class CssService extends Component
{
    public $useQueue = true;

    public function renderCss(): void
    {
        if ($this->isCssableRequest()) {
            $css = $this->getCssForRequest();
            Craft::$app->getView()->registerCss($css, $this->formatTagAttributes(Critter::getInstance()->settings->styleTagAttributes));
        } else {
            $request = Craft::$app->getRequest();
            $url = $request->getUrl();
            Critter::debug("Skipping CSS rendering for non-site request or unsupported request type: {$url}", 'css');
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

        // check if we have cached CSS
        $cssModel = Critter::getInstance()->storage->get($cssRequest);
        if (!$cssModel->isEmpty()) {
            // check if the CSS is expired
            if ($this->isCssExpired($cssRequest)) {
                // CSS is expired - handle according to settings
                $settings = Critter::getInstance()->getSettings();

                if ($settings->regenerateExpiredCss === Settings::REGENERATE_ON_REQUEST) {
                    // Regenerate on request - start generation and return existing CSS for now
                    if ($generate) {

                        Critter::getInstance()->log->info("CSS for URL '{$url->getAbsoluteUrl()}' is expired and will be regenerated.", 'css');

                        Critter::getInstance()->generator->startGenerate($cssRequest, $this->useQueue);
                    }
                    // Return the expired CSS while regeneration happens
                    return $cssModel->getCss();
                } else {
                    // Manual regeneration - return expired CSS without triggering regeneration

                    Critter::getInstance()->log->info("CSS for URL '{$url->getAbsoluteUrl()}' is expired and must be regenerated manually.", 'css');

                    return $cssModel->getCss();
                }
            }

            // CSS is not expired, return it
            return $cssModel->getCss();
        }

        // if there is no css in storage, start generating new css and return fallback css
        if ($generate) {
            Critter::getInstance()->generator->startGenerate($cssRequest, $this->useQueue);
        }

        // log
        Critter::info("Using fallback css for URL '{$url->getAbsoluteUrl()}'.", 'css');

        return Critter::getInstance()->fallbackService->getFallbackCss();
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

    /**
     * Check if CSS is expired based on the request record
     */
    private function isCssExpired(CssRequest $cssRequest): bool
    {
        $record = Critter::getInstance()->requestRecords->getRecordByCssRequest($cssRequest);

        if (!$record || !$record->expiryDate) {
            return false;
        }

        $now = new \DateTime();
        return $record->expiryDate < $now;
    }
}
