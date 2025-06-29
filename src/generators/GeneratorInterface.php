<?php

namespace mijewe\craftcriticalcssgenerator\generators;

use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

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
}
