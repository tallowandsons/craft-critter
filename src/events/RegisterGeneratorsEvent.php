<?php

namespace tallowandsons\critter\events;

use yii\base\Event;

/**
 * RegisterGeneratorsEvent class.
 */
class RegisterGeneratorsEvent extends Event
{
    /**
     * @var array The registered generators
     */
    public array $generators = [];
}
