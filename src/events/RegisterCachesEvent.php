<?php

namespace mijewe\critter\events;

use yii\base\Event;

/**
 * Register caches event
 */
class RegisterCachesEvent extends Event
{
    /**
     * @var array The cache classes
     */
    public array $caches = [];
}
