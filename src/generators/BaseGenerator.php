<?php

namespace mijewe\critter\generators;

use Craft;
use craft\base\Model;
use craft\web\twig\TemplateLoaderException;
use mijewe\critter\Critter;
use mijewe\critter\generators\GeneratorInterface;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;

class BaseGenerator extends Model implements GeneratorInterface
{
    /**
     * @var array Custom warnings that don't block saving
     */
    private array $warnings = [];

    /**
     * Add a warning message (doesn't block validation)
     */
    public function addWarning(string $attribute, string $message): void
    {
        if (!isset($this->warnings[$attribute])) {
            $this->warnings[$attribute] = [];
        }
        $this->warnings[$attribute][] = $message;
    }

    /**
     * Get warning messages for an attribute
     */
    public function getWarnings(string $attribute = null): array
    {
        if ($attribute !== null) {
            return $this->warnings[$attribute] ?? [];
        }
        return $this->warnings;
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(string $attribute = null): bool
    {
        if ($attribute !== null) {
            return !empty($this->warnings[$attribute]);
        }
        return !empty($this->warnings);
    }

    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url): GeneratorResponse
    {
        try {
            return $this->getCriticalCss($url);
        } catch (\Exception $e) {

            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $settings = array_merge(
            [
                'generator' => $this,
                'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ],
            $this->getSettings()
        );

        $templatePath = sprintf(
            '%s/cp/settings/includes/generators/%s/settings',
            Critter::getPluginHandle(),
            $this->handle ?? 'base'
        );

        try {
            return Craft::$app->getView()->renderTemplate($templatePath, $settings);
        } catch (TemplateLoaderException $e) {
            // Template file doesn't exist, return null
            return null;
        }
    }

    /**
     * return the settings for this generator.
     * This is used to display the settings in the CP.
     */
    public function getSettings(): array
    {
        return [];
    }

    /**
     * Get the critical CSS for the given URL.
     */
    protected function getCriticalCss(UrlModel $url): GeneratorResponse
    {
        return (new GeneratorResponse())->setSuccess(true);
    }
}
