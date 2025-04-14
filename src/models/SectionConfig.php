<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;
use craft\elements\Entry;
use craft\helpers\Json;
use honchoagency\craftcriticalcssgenerator\records\SectionConfigRecord;

/**
 * Section Config model
 */
class SectionConfig extends Model
{

    public ?int $entryId;

    /**
     * returns the entry for this section config
     */
    public function getEntry(): ?Entry
    {
        return Craft::$app->getEntries()->getEntryById($this->entryId);
    }

    /**
     * creates a new SectionConfig model from a SectionConfigRecord
     */
    static function createFromRecord(SectionConfigRecord $record): SectionConfig
    {
        $record = $record->toArray();
        $json = $record['data'] ?? [];
        $data = Json::decode($json);

        $model = new SectionConfig();
        $model->entryId = $data['entryId'] ?? null;

        return $model;
    }
}
