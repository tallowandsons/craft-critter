<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\generators\DummyGenerator;

/**
 * Critical CSS Generator settings
 */
class Settings extends Model
{
    public string $generator = DummyGenerator::class;
}
