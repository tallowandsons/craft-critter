<?php

namespace mijewe\critter\generators;

use Craft;
use craft\helpers\Console;
use mijewe\critter\Critter;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;
use Symfony\Component\Process\Process;

class CriticalCssCliGenerator extends BaseGenerator
{

    public string $handle = 'critical-css-cli';

    public int $timeout = 60;

    // viewport dimensions for critical CSS generation (default from @plone/critical-css-cli)
    public int $width = 1300;
    public int $height = 900;

    public function __construct()
    {
        $generatorSettings = Critter::getInstance()->settings->generatorSettings ?? [];

        // Load timeout and dimensions from settings, with fallback to defaults
        $this->timeout = (int)($generatorSettings['timeout'] ?? $this->timeout);
        $this->width = (int)($generatorSettings['width'] ?? $this->width);
        $this->height = (int)($generatorSettings['height'] ?? $this->height);

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Critter::translate('@plone/critical-css-cli Generator');
    }

    /**
     * Return the settings for this generator.
     * This is used to display the settings in the CP.
     */
    public function getSettings(): array
    {
        return [
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
            'pluginHandle' => Critter::getPluginHandle(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        $url = $urlModel->getAbsoluteUrl();
        $key = $this->getFilenameFromUrl($url);

        $outputPath = Craft::getAlias('@storage/runtime/temp/critical');
        $outputName = "$key.css";
        $output = $outputPath . '/' . $outputName;

        $command = [
            'node',
            'node_modules/@plone/critical-css-cli',
            '-o',
            $output,
            '--dimensions',
            "{$this->width}x{$this->height}",
            $url
        ];

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->run();

            Console::stdout($process->getOutput() . PHP_EOL, Console::FG_GREEN);
            Console::stdout($process->getErrorOutput() . PHP_EOL, Console::FG_RED);

            if ($process->isSuccessful() && is_readable($output)) {
                $cssStr = file_get_contents($output);

                return (new GeneratorResponse())
                    ->setSuccess(true)
                    ->setCss(new CssModel($cssStr));
            } else {
                return (new GeneratorResponse())
                    ->setSuccess(false)
                    ->setException(new \Exception($process->getErrorOutput()));
            }
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException(new \Exception("Critical CSS generation timed out after {$this->timeout} seconds. Consider increasing the timeout setting."));
        } catch (\Exception $e) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException($e);
        }
    }

    private function getFilenameFromUrl(string $url): string
    {
        return md5($url);
    }
}
