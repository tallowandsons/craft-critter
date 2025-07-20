<?php

namespace mijewe\critter\generators;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;

/**
 * No Generator - A safe default that doesn't generate any critical CSS
 *
 * This generator is used as the default option to ensure that critical CSS
 * generation is opt-in rather than accidentally enabled with external services.
 * Users must explicitly choose and configure a real generator.
 */
class NoGenerator extends BaseGenerator
{
    public string $handle = 'none';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Critter::translate('No Generator (disabled)');
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        // Always return a failed response with a helpful message
        return (new GeneratorResponse())
            ->setSuccess(false)
            ->setException(new \Exception(
                Critter::translate('No generator is configured. Please select and configure a critical CSS generator in the plugin settings.')
            ));
    }
}
