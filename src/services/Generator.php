<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use craft\web\Request;
use honchoagency\craftcriticalcssgenerator\Critical;
use yii\base\Component;

/**
 * Generator service
 */
class Generator extends Component
{
    public function generate(Request $request, bool $useQueue = true)
    {

        $path = $request->getPathInfo();

        if ($useQueue) {
            // queue job
            // Craft::$app->queue->push(new GenerateCriticalCssJob([
            //     'request' => $request,
            // ]));
        } else {
            // generate css
            $css = $this->generateCss($request);

            // save to storage
            Critical::getInstance()->storage->set($path, $css);
        }
    }

    public function generateCss(Request $request)
    {
        $path = $request->getPathInfo();

        $key = Critical::getInstance()->storage->getCacheKey($path);

        $css = "/* key: {$key} */ body { background-color: lime; }";

        return $css;
    }
}
