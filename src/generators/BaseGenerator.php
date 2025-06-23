<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use craft\base\Component;
use honchoagency\craftcriticalcssgenerator\generators\GeneratorInterface;
use honchoagency\craftcriticalcssgenerator\models\GeneratorResponse;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

class BaseGenerator extends Component implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url): GeneratorResponse
    {
        try {
            return $this->getCriticalCss($url);
        } catch (\Exception $e) {

            $response = new GeneratorResponse();
            $response->setSuccess(false);
            $response->setException($e);

            return $response;
        }
    }

    /**
     * Get the critical CSS for the given URL.
     */
    protected function getCriticalCss(UrlModel $url): GeneratorResponse
    {
        return new GeneratorResponse();
    }
}
