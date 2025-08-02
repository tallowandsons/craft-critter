<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\helpers\App;
use craft\models\Site;
use tallowandsons\critter\Critter;
use yii\base\Component;

/**
 * Fallback CSS service
 * Handles all fallback CSS related functionality including file path generation,
 * CSS retrieval, validation, and stamping.
 */
class FallbackService extends Component
{
    /**
     * Default fallback CSS content when no other fallback is available
     */
    public string $fallbackCss = "/* */";

    /**
     * Get fallback CSS content with automatic fallback chain:
     * 1. Site-specific generated file
     * 2. Manual configured file
     * 3. Default empty CSS
     *
     * @param int|null $siteId Optional site ID, defaults to current site
     * @return string Stamped CSS content
     */
    public function getFallbackCss(?int $siteId = null): string
    {
        $css = '';
        $source = 'default';

        // Determine which site to use
        $site = $siteId ? Craft::$app->getSites()->getSiteById($siteId) : Craft::$app->getSites()->getCurrentSite();
        if (!$site) {
            return $this->stampFallbackCss('', 'error: site not found');
        }

        // Try site-specific generated fallback CSS first
        $siteSpecificFile = $this->getSiteSpecificFallbackPath($site);
        $css = $this->loadCssFromFile($siteSpecificFile);
        if ($css) {
            $source = "generated ({$site->name})";
            Critter::getInstance()->log->debug("Loaded site-specific generated fallback CSS from runtime: {$siteSpecificFile}", 'css');
        }

        // Check if fallback CSS file path is configured (if no generated CSS loaded)
        if (!$css) {
            $css = $this->loadManualFallbackCss();
            if ($css) {
                $source = 'file';
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
     * Generate the file path for site-specific generated fallback CSS
     *
     * @param Site $site
     * @return string Full file path
     */
    public function getSiteSpecificFallbackPath(Site $site): string
    {
        return $this->getFallbackDirectory() . DIRECTORY_SEPARATOR . "{$site->handle}.css";
    }

    /**
     * Generate the directory path for fallback CSS files
     *
     * @return string Directory path
     */
    public function getFallbackDirectory(): string
    {
        $runtimePath = Craft::$app->getPath()->getRuntimePath();
        return $runtimePath . DIRECTORY_SEPARATOR . Critter::getPluginHandle() . DIRECTORY_SEPARATOR . 'fallback';
    }

    /**
     * Save fallback CSS content to site-specific file in runtime
     *
     * @param string $css CSS content to save
     * @param int $siteId Site ID for naming the file
     * @return string|null File path on success, null on failure
     */
    public function saveFallbackCssToRuntime(string $css, int $siteId): ?string
    {
        try {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            if (!$site) {
                throw new \Exception("Site not found with ID: {$siteId}");
            }

            $fallbackDir = $this->getFallbackDirectory();

            // Ensure directory exists
            if (!is_dir($fallbackDir)) {
                mkdir($fallbackDir, 0755, true);
            }

            $fallbackFile = $this->getSiteSpecificFallbackPath($site);

            if (file_put_contents($fallbackFile, $css) !== false) {
                return $fallbackFile;
            }

            throw new \Exception("Failed to write to file: {$fallbackFile}");
        } catch (\Exception $e) {
            Critter::error("Failed to save fallback CSS to runtime: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Check if a site has generated fallback CSS file
     *
     * @param Site $site
     * @return bool
     */
    public function hasGeneratedFallbackCss(Site $site): bool
    {
        $filePath = $this->getSiteSpecificFallbackPath($site);
        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Clear generated fallback CSS file for a specific site
     *
     * @param Site $site
     * @return bool Success status
     */
    public function clearGeneratedFallbackCss(Site $site): bool
    {
        $filePath = $this->getSiteSpecificFallbackPath($site);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true; // Consider it successful if file doesn't exist
    }

    /**
     * Get all sites that have generated fallback CSS files
     *
     * @return array Array of site IDs
     */
    public function getSitesWithGeneratedFallbackCss(): array
    {
        $siteIds = [];
        $allSites = Craft::$app->getSites()->getAllSites();

        foreach ($allSites as $site) {
            if ($this->hasGeneratedFallbackCss($site)) {
                $siteIds[] = $site->id;
            }
        }

        return $siteIds;
    }

    /**
     * Get sites with configured fallback entries
     *
     * @return array Array of site info with entry details
     */
    public function getSitesWithFallbackEntries(): array
    {
        $sitesWithFallback = [];
        $allSites = Craft::$app->getSites()->getAllSites();

        foreach ($allSites as $site) {
            $fallbackEntryId = Critter::getInstance()->configService->getFallbackCssEntryId($site->id);
            if ($fallbackEntryId) {
                $fallbackEntry = \craft\elements\Entry::find()->id($fallbackEntryId)->one();
                if ($fallbackEntry) {
                    $sitesWithFallback[] = [
                        'site' => $site,
                        'entryId' => $fallbackEntryId,
                        'entry' => $fallbackEntry
                    ];
                }
            }
        }

        return $sitesWithFallback;
    }

    /**
     * Get sites without configured fallback entries (or with invalid entries)
     *
     * @return array Array of site info with error details
     */
    public function getSitesWithoutFallbackEntries(): array
    {
        $sitesWithoutFallback = [];
        $allSites = Craft::$app->getSites()->getAllSites();

        foreach ($allSites as $site) {
            $fallbackEntryId = Critter::getInstance()->configService->getFallbackCssEntryId($site->id);
            if ($fallbackEntryId) {
                $fallbackEntry = \craft\elements\Entry::find()->id($fallbackEntryId)->one();
                if (!$fallbackEntry) {
                    $sitesWithoutFallback[] = [
                        'site' => $site,
                        'error' => 'Entry not found (ID: ' . $fallbackEntryId . ')'
                    ];
                }
            } else {
                $sitesWithoutFallback[] = [
                    'site' => $site,
                    'error' => 'No fallback entry configured'
                ];
            }
        }

        return $sitesWithoutFallback;
    }

    /**
     * Get site options for checkbox select fields
     *
     * @return array Array formatted for forms.checkboxSelectField
     */
    public function getSiteOptionsForForms(): array
    {
        $options = [];
        $allSites = Craft::$app->getSites()->getAllSites();

        foreach ($allSites as $site) {
            $hasFallback = Critter::getInstance()->configService->getFallbackCssEntryId($site->id) !== null;
            $options[] = [
                'label' => $site->name . ($hasFallback ? '' : ' (Missing fallback entry)'),
                'value' => $site->id,
                'disabled' => !$hasFallback
            ];
        }

        return $options;
    }

    /**
     * Load CSS content from a manual fallback CSS file (from settings)
     *
     * @return string|null CSS content or null if not available/valid
     */
    private function loadManualFallbackCss(): ?string
    {
        $settings = Critter::getInstance()->getSettings();

        if (!$settings->fallbackCssFilePath) {
            return null;
        }

        $filePath = App::parseEnv($settings->fallbackCssFilePath);

        // Security validations
        if (!$this->isValidFallbackCssPath($filePath)) {
            Critter::getInstance()->log->warning("Fallback CSS file path failed security validation: {$filePath}", 'css');
            return null;
        }

        return $this->loadCssFromFile($filePath);
    }

    /**
     * Load CSS content from a file with error handling
     *
     * @param string $filePath
     * @return string|null CSS content or null on failure
     */
    private function loadCssFromFile(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        try {
            $css = file_get_contents($filePath);
            if ($css !== false) {
                Critter::getInstance()->log->debug("Loaded fallback CSS from file: {$filePath}", 'css');
                return $css;
            }
        } catch (\Throwable $e) {
            Critter::getInstance()->log->error("Failed to read fallback CSS file '{$filePath}': " . $e->getMessage(), 'css');
        }

        return null;
    }

    /**
     * Add a comment stamp to CSS indicating it's fallback CSS
     *
     * @param string $css
     * @param string $source
     * @return string Stamped CSS
     */
    private function stampFallbackCss(string $css, string $source): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $stamp = "/* CRITTER FALLBACK CSS - Source: {$source} - Generated: {$timestamp} */\n";

        return $stamp . $css;
    }

    /**
     * Validate that a fallback CSS file path is secure and appropriate
     *
     * @param string|null $filePath
     * @return bool
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
     *
     * @return array
     */
    private function getAllowedFallbackCssPaths(): array
    {
        return [
            Craft::$app->getPath()->getStoragePath(),
        ];
    }
}
