<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use yii\base\Component;

/**
 * Generator service
 */
class Generator extends Component
{

    public GeneratorInterface $generator;

    public function __construct()
    {
        $generatorClass = Critical::getInstance()->settings->generatorType;
        $this->generator = new $generatorClass();
    }

    public function generate(string $url, bool $useQueue = true, bool $storeResult = true): void
    {
        if ($useQueue) {
            Craft::$app->queue->push(new GenerateCriticalCssJob([
                'url' => $url,
                'storeResult' => $storeResult,
            ]));
        } else {
            $this->generator->generate($url, $storeResult);
        }
    }
}
