<?php

namespace honchoagency\craftcriticalcssgenerator\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * Uri Record record
 */
class UriRecord extends ActiveRecord
{

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_ERROR = 'error';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_TODO = 'todo';
    public const STATUS_GENERATING = 'generating';

    public static function tableName()
    {
        return '{{%criticalcssgenerator_uris}}';
    }

    public function isInQueue()
    {
        return $this->status === self::STATUS_QUEUED || $this->status === self::STATUS_GENERATING;
    }
}
