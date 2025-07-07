<?php

namespace mijewe\craftcriticalcssgenerator\generators;

use Craft;
use craft\base\Component;
use craft\web\twig\TemplateLoaderException;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\generators\GeneratorInterface;
use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

class BaseGenerator extends Component implements GeneratorInterface
{
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
            Critical::getPluginHandle(),
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
