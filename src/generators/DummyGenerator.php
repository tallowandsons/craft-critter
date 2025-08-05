<?php

namespace tallowandsons\critter\generators;

use Craft;
use tallowandsons\critter\Critter;
use tallowandsons\critter\exceptions\RetryableCssGenerationException;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\GeneratorResponse;
use tallowandsons\critter\models\UrlModel;

class DummyGenerator extends BaseGenerator
{
    public string $handle = 'dummy';

    private string $css = "body { background-color: pink; }";

    /**
     * @var bool Whether to return success or failure
     */
    public bool $success = true;

    /**
     * @var string|null The type of exception to throw (if any)
     * Options: null, 'RetryableCssGenerationException'
     */
    public ?string $exceptionType = null;

    public function __construct()
    {
        try {
            $generatorSettings = Critter::getInstance()->settings->generatorSettings ?? [];
            // Load settings from configuration using setAttributes
            $this->setAttributes($generatorSettings, false);
        } catch (\Exception $e) {
            // Handle case where Critter isn't available (e.g., in tests)
            // Use default values
        }

        parent::__construct();
    }

    public static function displayName(): string
    {
        return 'Dummy Generator';
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        // Run validation to populate warnings/errors for display
        $this->validate();

        return [
            'generator' => $this,
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
            'pluginHandle' => Critter::getPluginHandle(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['success'], 'boolean'],
            [['exceptionType'], 'string'],
            [['exceptionType'], 'in', 'range' => [
                '',
                'RetryableCssGenerationException',
            ]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'success' => Critter::translate('Success'),
            'exceptionType' => Critter::translate('Exception Type'),
        ];
    }

    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        // Check if we should throw an exception
        if ($this->exceptionType === 'RetryableCssGenerationException') {
            throw new RetryableCssGenerationException('Dummy generator throwing RetryableCssGenerationException for testing');
        }

        $generatorResponse = new GeneratorResponse();

        if ($this->success) {
            $generatorResponse->setSuccess(true);
            $generatorResponse->setCss(new CssModel($this->css));
        } else {
            $generatorResponse->setSuccess(false);
            $generatorResponse->setException(new \Exception('Dummy generator configured to fail'));
        }

        return $generatorResponse;
    }
}
