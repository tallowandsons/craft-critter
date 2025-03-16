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
    public function generate(UrlModel $urlModel, bool $storeResult = true, bool $resolveCache = true): void
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
                if ($storeResult) {
                    $css = file_get_contents($output);
                    $this->store($urlModel, new CssModel($css));
                }

                if ($resolveCache) {
                    $this->resolveCache($urlModel);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
