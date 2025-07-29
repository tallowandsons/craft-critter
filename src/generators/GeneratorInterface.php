<?php

namespace mijewe\critter\generators;

use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;

interface GeneratorInterface
{
    /**
     * Generate the critical CSS for the given URL
     */
    public function generate(UrlModel $url): GeneratorResponse;

    /**
     * Get the HTML for the generator CMS settings
     */
    public function getSettingsHtml(): ?string;

    /**
     * Check if the generator is properly configured and ready for generation
     */
    public function isReadyForGeneration(): bool;
}
