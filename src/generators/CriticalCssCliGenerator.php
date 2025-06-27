<?php

namespace mijewe\craftcriticalcssgenerator\generators;

use Craft;
use mijewe\craftcriticalcssgenerator\models\CssModel;
use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
use Symfony\Component\Process\Process;

class CriticalCssCliGenerator extends BaseGenerator
{

    public int $timeout = 60;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('critical-css-generator', '@plone/critical-css-cli Generator');
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
