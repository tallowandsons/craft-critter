<?php

namespace mijewe\critter\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * Request Record record
 */
class RequestRecord extends ActiveRecord
{

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_ERROR = 'error';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_TODO = 'todo';
    public const STATUS_GENERATING = 'generating';

    public static function tableName()
    {
        return '{{%critter_requests}}';
    }

    public function isInQueue()
    {
        return $this->status === self::STATUS_QUEUED || $this->status === self::STATUS_GENERATING;
    }
}
