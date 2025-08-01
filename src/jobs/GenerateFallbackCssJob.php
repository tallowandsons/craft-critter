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
    public int $entryId;

    /**
     * @inheritdoc
     */
    protected function performCssGeneration(): void
    {
        // Get the entry
        $entry = Entry::find()->id($this->entryId)->one();
        if (!$entry) {
            throw new \Exception("Entry not found with ID: {$this->entryId}");
        }

        Craft::info(
            "Starting fallback CSS generation for entry: {$entry->title} (ID: {$this->entryId})",
            Critter::getPluginHandle()
        );

        // Generate CSS for the entry's URL
        $url = UrlFactory::createFromEntry($entry);
        $css = Critter::getInstance()->css->getCssForUrl($url, true);

        if (!$css) {
            throw new \Exception("Failed to generate CSS for entry: {$entry->title}");
        }

        // Save CSS to runtime file
        $fallbackPath = Critter::getInstance()->utilityService->saveFallbackCssToRuntime($css);
        if (!$fallbackPath) {
            throw new \Exception("Failed to save fallback CSS to runtime");
        }

        // Update settings to use generated fallback CSS
        $settings = Critter::getInstance()->getSettings();
        $settings->useGeneratedFallbackCss = true;
        $settings->fallbackCssEntryId = $this->entryId;

        // Save settings via the plugin
        $plugin = Critter::getInstance();
        Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray());

        Craft::info(
            "Successfully generated fallback CSS from entry: {$entry->title} (ID: {$this->entryId})",
            Critter::getPluginHandle()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getJobContext(): string
    {
        return "Entry ID: {$this->entryId}";
    }

    /**
     * @inheritdoc
     */
    protected function createRetryJob(int $nextAttempt): GenerateCssBaseJob
    {
        return new self([
            'entryId' => $this->entryId,
            'retryAttempt' => $nextAttempt
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getJobDescription(): string
    {
        return 'Generating fallback CSS for entry ID ' . $this->entryId;
    }
}
