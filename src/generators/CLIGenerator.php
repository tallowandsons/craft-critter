<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use Craft;
use honchoagency\craftcriticalcssgenerator\models\CssModel;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use Symfony\Component\Process\Process;

class CLIGenerator extends BaseGenerator
{

    public int $timeout = 60;

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): CssModel
    {
        $url = $urlModel->getUrl();
        $key = $urlModel->getSafeUrl();

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
                return new CssModel($cssStr);
            } else {
                return new CssModel();
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
