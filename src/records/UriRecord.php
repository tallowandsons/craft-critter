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

    public static function tableName()
    {
        return '{{%criticalcssgenerator_uris}}';
    }
}
