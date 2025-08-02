<?php

namespace tallowandsons\critter\jobs;

use Craft;
use craft\elements\Entry;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;

/**
 * Generate Fallback CSS Job
 */
class GenerateFallbackCssJob extends GenerateCssBaseJob
{
    public ?int $siteId = null;

    /**
     * @inheritdoc
     */
    protected function performCssGeneration(): void
    {
        // Abort if no site ID is specified
        if ($this->siteId === null) {
            throw new \Exception("Site ID must be specified for fallback CSS generation");
        }

        $site = Craft::$app->getSites()->getSiteById($this->siteId);
        if (!$site) {
            throw new \Exception("Site not found with ID: {$this->siteId}");
        }

        // Get the fallback entry ID for this specific site
        $entryId = Critter::getInstance()->configService->getFallbackCssEntryId($this->siteId);
        if (!$entryId) {
            throw new \Exception("No fallback entry configured for site: {$site->name} (ID: {$this->siteId})");
        }

        // Get the entry
        $entry = Entry::find()->id($entryId)->one();
        if (!$entry) {
            throw new \Exception("Entry not found with ID: {$entryId} for site: {$site->name}");
        }

        Craft::info(
            "Starting fallback CSS generation for entry: {$entry->title} (ID: {$entryId}) on site: {$site->name} (ID: {$this->siteId})",
            Critter::getPluginHandle()
        );

        // Generate CSS for the entry's URL on the specified site
        $url = UrlFactory::createFromEntry($entry, $this->siteId);
        $css = Critter::getInstance()->css->getCssForUrl($url, true);

        if (!$css) {
            throw new \Exception("Failed to generate CSS for entry: {$entry->title} on site: {$site->name}");
        }

        // Save CSS to runtime file with site-specific naming
        $fallbackPath = $this->saveFallbackCssToRuntime($css, $this->siteId);
        if (!$fallbackPath) {
            throw new \Exception("Failed to save fallback CSS to runtime for site: {$site->name}");
        }

        Craft::info(
            "Successfully generated fallback CSS from entry: {$entry->title} (ID: {$entryId}) for site: {$site->name}",
            Critter::getPluginHandle()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getJobContext(): string
    {
        $context = "Site ID: {$this->siteId}";
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
            $siteName = $site ? $site->name : $this->siteId;
            $context = "Site: {$siteName} (ID: {$this->siteId})";
        }
        return $context;
    }

    /**
     * @inheritdoc
     */
    protected function createRetryJob(int $nextAttempt): GenerateCssBaseJob
    {
        return new self([
            'siteId' => $this->siteId,
            'retryAttempt' => $nextAttempt
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getJobDescription(): string
    {
        $description = 'Generating fallback CSS';
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
            $siteName = $site ? $site->name : $this->siteId;
            $description .= " for site: {$siteName}";
        }
        return $description;
    }

    /**
     * Save fallback CSS content to runtime with site-specific naming
     */
    private function saveFallbackCssToRuntime(string $css, int $siteId): ?string
    {
        return Critter::getInstance()->fallbackService->saveFallbackCssToRuntime($css, $siteId);
    }
}
