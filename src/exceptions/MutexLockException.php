<?php

namespace mijewe\critter\exceptions;

/**
 * Exception thrown when a mutex lock cannot be acquired
 * This indicates a temporary condition that should be retried
 */
class MutexLockException extends \Exception {}
