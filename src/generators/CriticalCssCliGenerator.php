<?php

namespace mijewe\critter\generators;

use Craft;
use mijewe\critter\Critter;
use mijewe\critter\models\CssModel;
use mijewe\critter\models\GeneratorResponse;
use mijewe\critter\models\UrlModel;
use Symfony\Component\Process\Process;

class CriticalCssCliGenerator extends BaseGenerator
{

    public string $handle = 'critical-css-cli';

    public int $timeout = 60;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Critter::translate('@plone/critical-css-cli Generator');
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

        $command = "node node_modules/@plone/critical-css-cli -o $output $url";

        $process = new Process(explode(' ', $command));
        $process->setTimeout($this->timeout);
        $process->setWorkingDirectory(CRAFT_BASE_PATH);

        $process->start();

        // TODO: colourful output
        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                echo "\nRead from stdout: " . $data;
            } else { // $process::ERR === $type
                echo "\nRead from stderr: " . $data;
            }
        }

        try {
            if ($process->isSuccessful()) {
                $cssStr = file_get_contents($output);

                $generatorResponse = new GeneratorResponse();
                $generatorResponse->setSuccess(true);
                $generatorResponse->setCss(new CssModel($cssStr));
                return $generatorResponse;
            } else {
                return new GeneratorResponse();
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getFilenameFromUrl(string $url): string
    {
        return md5($url);
    }
}
