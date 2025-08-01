<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\helpers\App;
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

        return (new CssModel($this->getFallbackCss()))->getCss();
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

    /**
     * Get fallback CSS content - either from generated file, configured file, or default fallback
     * Stamps the CSS with a comment indicating it's fallback CSS
     */
    private function getFallbackCss(): string
    {
        $settings = Critter::getInstance()->getSettings();
        $css = '';
        $source = 'default';

        // Check if using generated fallback CSS first
        if ($settings->useGeneratedFallbackCss) {
            $runtimePath = Craft::$app->getPath()->getRuntimePath();

            // Try site-specific fallback CSS first
            $currentSite = Craft::$app->getSites()->getCurrentSite();
            $siteSpecificFile = $runtimePath . DIRECTORY_SEPARATOR . Critter::getPluginHandle() . DIRECTORY_SEPARATOR . "fallback-{$currentSite->handle}.css";

            if (file_exists($siteSpecificFile) && is_readable($siteSpecificFile)) {
                try {
                    $css = file_get_contents($siteSpecificFile);
                    if ($css !== false) {
                        $source = "generated ({$currentSite->name})";
                        Critter::getInstance()->log->debug("Loaded site-specific generated fallback CSS from runtime: {$siteSpecificFile}", 'css');
                    }
                } catch (\Throwable $e) {
                    Critter::getInstance()->log->error("Failed to read site-specific generated fallback CSS file '{$siteSpecificFile}': " . $e->getMessage(), 'css');
                }
            }

            // Fall back to generic fallback file if site-specific not found
            if (!$css) {
                $fallbackFile = $runtimePath . DIRECTORY_SEPARATOR . 'critter' . DIRECTORY_SEPARATOR . 'fallback.css';

                if (file_exists($fallbackFile) && is_readable($fallbackFile)) {
                    try {
                        $css = file_get_contents($fallbackFile);
                        if ($css !== false) {
                            $source = 'generated (generic)';
                            Critter::getInstance()->log->debug("Loaded generic generated fallback CSS from runtime: {$fallbackFile}", 'css');
                        }
                    } catch (\Throwable $e) {
                        Critter::getInstance()->log->error("Failed to read generated fallback CSS file '{$fallbackFile}': " . $e->getMessage(), 'css');
                    }
                } else {
                    Critter::getInstance()->log->warning("Generated fallback CSS file not found: {$fallbackFile}", 'css');
                }
            }
        }

        // Check if fallback CSS file path is configured (if no generated CSS loaded)
        if (!$css && $settings->fallbackCssFilePath) {
            $filePath = App::parseEnv($settings->fallbackCssFilePath);

            // Security validations
            if ($this->isValidFallbackCssPath($filePath)) {
                try {
                    $css = file_get_contents($filePath);
                    if ($css !== false) {
                        $source = 'file';
                        Critter::getInstance()->log->debug("Loaded fallback CSS from file: {$filePath}", 'css');
                    }
                } catch (\Throwable $e) {
                    Critter::getInstance()->log->error("Failed to read fallback CSS file '{$filePath}': " . $e->getMessage(), 'css');
                }
            } else {
                Critter::getInstance()->log->warning("Fallback CSS file path failed security validation: {$filePath}", 'css');
            }
        }

        // Fall back to the default empty fallback CSS
        if (!$css) {
            $css = $this->fallbackCss;
        }

        // Stamp the CSS with a fallback indicator comment
        return $this->stampFallbackCss($css, $source);
    }

    /**
     * Add a comment stamp to CSS indicating it's fallback CSS
     */
    private function stampFallbackCss(string $css, string $source): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $stamp = "/* CRITTER FALLBACK CSS - Source: {$source} - Generated: {$timestamp} */\n";

        return $stamp . $css;
    }

    /**
     * Validate that a fallback CSS file path is secure and appropriate
     */
    private function isValidFallbackCssPath(?string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }

        // Check if file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        // Resolve the real path to prevent path traversal attacks
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return false;
        }

        // Get allowed base paths
        $allowedPaths = $this->getAllowedFallbackCssPaths();

        // Check if the real path starts with any of the allowed paths
        $isAllowed = false;
        foreach ($allowedPaths as $allowedPath) {
            $allowedRealPath = realpath($allowedPath);
            if ($allowedRealPath && strpos($realPath, $allowedRealPath) === 0) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            Critter::getInstance()->log->warning("Fallback CSS file outside allowed paths: {$realPath}", 'css');
            return false;
        }

        // Check file extension
        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        if ($extension !== 'css') {
            Critter::getInstance()->log->warning("Fallback CSS file must have .css extension: {$realPath}", 'css');
            return false;
        }

        // Check file size (prevent reading huge files)
        $maxSize = 1024 * 1024; // 1MB limit
        if (filesize($realPath) > $maxSize) {
            Critter::getInstance()->log->warning("Fallback CSS file too large (max 1MB): {$realPath}", 'css');
            return false;
        }

        return true;
    }

    /**
     * Get allowed base paths for fallback CSS files
     */
    private function getAllowedFallbackCssPaths(): array
    {
        return [
            Craft::$app->getPath()->getStoragePath(),
        ];
    }
}
