<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use honchoagency\craftcriticalcssgenerator\Critical;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\jobs\GenerateCriticalCssJob;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use yii\base\Component;

/**
 * Generator service
 */
class GeneratorService extends Component
{

    public GeneratorInterface $generator;

    public function __construct()
    {
        $generatorClass = Critical::getInstance()->settings->generatorType;
        $this->generator = new $generatorClass();
    }

    /**
     * Start generating critical css for a url, optionally using the queue
     */
    public function startGenerate(UrlModel $url, bool $useQueue = true, bool $storeResult = true): void
    {
        if ($useQueue) {
            Critical::getInstance()->queueService->pushIfNew(new GenerateCriticalCssJob([
                'url' => $url,
                'storeResult' => $storeResult,
            ]));
        } else {
            $this->generate($url, $storeResult);
        }
    }

    /**
     * Generate critical css for a url.
     * This is different from startGenerate as it will always generate the css immediately, not using the queue.
     */
    public function generate(UrlModel $url, bool $storeResult): void
    {
        $this->generator->generate($url, $storeResult);
    }
}
