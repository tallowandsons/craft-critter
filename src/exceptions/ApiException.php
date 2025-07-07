<?php

namespace mijewe\critter\exceptions;

use yii\base\Exception;

class ApiException extends Exception
{
    public function getName(): string
    {
        return 'Api Exception';
    }
}
