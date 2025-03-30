<?php

namespace honchoagency\craftcriticalcssgenerator\generators;

use honchoagency\craftcriticalcssgenerator\models\GeneratorResponse;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;

interface GeneratorInterface
{
    /**
     * Generate the critical CSS for the given URL
     */
    public function generate(UrlModel $url): GeneratorResponse;
}
