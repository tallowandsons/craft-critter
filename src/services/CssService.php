<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\elements\User;
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
     * Only generate CSS for legitimate web page requests that browsers would render.
     */
    public function isCssableRequest(): bool
    {
        $request = Craft::$app->getRequest();
        $url = $request->getUrl();

        // Check if this is a site request
        if (!$request->getIsSiteRequest()) {
            Critter::debug("Skipping CSS generation for non-site request: {$url}", 'css');
            return false;
        }

        // Check if this is a GET request
        if (!$request->getIsGet()) {
            $method = $request->getMethod();
            $userAgent = $request->getUserAgent();
            Critter::debug("Skipping CSS generation for non-GET request: {$url} (Method: {$method}, User-Agent: {$userAgent})", 'css');
            return false;
        }

        // Check if this is a console request
        if ($request->getIsConsoleRequest()) {
            Critter::debug("Skipping CSS generation for console request: {$url}", 'css');
            return false;
        }

        // Check if this is a preview request
        if ($request->getIsPreview()) {
            Critter::debug("Skipping CSS generation for preview request: {$url}", 'css');
            return false;
        }

        // Check if this is an action request
        if ($request->getIsActionRequest()) {
            Critter::debug("Skipping CSS generation for action request: {$url}", 'css');
            return false;
        }

        // drop request if excluded by the `ignorePatterns` setting
        if ($this->isIgnoredUrl($url)) {
            Critter::debug("Skipping CSS generation for ignored URL pattern: {$url}", 'css');
            return false;
        }

        // Check if the response is OK first (before other checks that might depend on it)
        $response = Craft::$app->getResponse();
        if ($response && !$response->getIsOk()) {
            $statusCode = $response->getStatusCode();
            Critter::debug("Skipping CSS generation for non-OK response (status {$statusCode}): {$url}", 'css');
            return false;
        }

        // Check if the request accepts HTML - be more lenient here
        $acceptHeader = $request->getHeaders()->get('Accept', '');
        $acceptsHtml = $request->accepts('text/html') ||
            str_contains($acceptHeader, '*/*') ||
            empty($acceptHeader);

        if (!$acceptsHtml) {
            $contentType = $request->getContentType();
            $userAgent = $request->getUserAgent();
            Critter::debug("Skipping CSS generation for non-HTML request: {$url} (Accept: {$acceptHeader}, Content-Type: {$contentType}, User-Agent: {$userAgent})", 'css');
            return false;
        }

        // Check site live status with user permissions context
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();
        if (!Craft::$app->getIsLive()) {
            // If site is not live, check if user has permission to access it
            if ($user === null || !$user->can('accessSiteWhenSystemIsOff')) {
                Critter::debug("Skipping CSS generation for offline site (user lacks permission): {$url}", 'css');
                return false;
            }
        }

        // Skip if user has debug toolbar enabled (would interfere with CSS)
        if ($user !== null && $user->getPreference('enableDebugToolbarForSite')) {
            Critter::debug("Skipping CSS generation because debug toolbar is enabled: {$url}", 'css');
            return false;
        }

        // Check for no-critter parameter
        if (!empty($request->getParam('no-critter'))) {
            Critter::debug("Skipping CSS generation due to no-critter parameter: {$url}", 'css');
            return false;
        }

        // Log successful validation
        $method = $request->getMethod();
        $userAgent = $request->getUserAgent();
        Critter::debug("CSS generation ALLOWED for request: {$url} (Method: {$method}, Accept: {$acceptHeader}, User-Agent: {$userAgent})", 'css');

        return true;
    }

    private function isIgnoredUrl(string $url): bool
    {
        $settings = Critter::getInstance()->getSettings();
        foreach ($settings->ignorePatterns as $patternArray) {
            if ($patternArray['enabled'] !== true || empty($patternArray['pattern'])) {
                continue;
            }
            if (@preg_match($patternArray['pattern'], $url)) {
                return true;
            }
        }
        return false;
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
