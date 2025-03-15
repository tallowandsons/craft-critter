<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CLIGenerator extends BaseGenerator
{

    public int $timeout = 60;

    public function generate(string $url, bool $storeResult = true): void
    {
        $key = Critical::getInstance()->storage->normaliseUriPath($url);

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
                    $this->store($url, $css);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
