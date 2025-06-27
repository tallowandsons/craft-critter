<?php

namespace mijewe\craftcriticalcssgenerator\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * Section Config Record record
 *
 * @property int $id ID
 * @property int|null $siteId Site ID
 * @property string|null $uri Uri
 * @property string|null $status Status
 * @property array|null $data Data
 * @property string|null $dateQueued Date queued
 * @property string|null $dateGenerated Date generated
 * @property string|null $expiryDate Expiry date
 */
class SectionConfigRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%criticalcssgenerator_sectionconfig}}';
    }
}
