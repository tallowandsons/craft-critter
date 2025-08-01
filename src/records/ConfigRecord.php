<?php

namespace tallowandsons\critter\records;

use craft\db\ActiveRecord;

/**
 * Config Record
 *
 * @property int $id ID
 * @property string $key Config key
 * @property string|null $value Config value
 * @property int|null $siteId Site ID
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid UID
 */
class ConfigRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%critter_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['key'], 'required'],
            [['key'], 'string', 'max' => 255],
            [['value'], 'string'],
            [['siteId'], 'integer'],
            [['key', 'siteId'], 'unique', 'targetAttribute' => ['key', 'siteId']],
        ];
    }
}
