<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use Craft;
use craft\helpers\Json;
use honchoagency\craftcriticalcssgenerator\models\SectionConfig;
use honchoagency\craftcriticalcssgenerator\records\SectionConfigRecord;
use yii\base\Component;

/**
 * Config Service service
 */
class ConfigService extends Component
{
    /**
     * saves all plugin config to the database
     * returns true on success
     */
    public function save(array $config): bool
    {
        $sections = $config['sections'] ?? [];

        $this->saveSections($sections);

        return true;
    }

    /**
     * saves the section configs to the database
     * returns true on success
     */
    private function saveSections(array $sections): bool
    {
        foreach ($sections as $sectionId => $sites) {
            foreach ($sites as $siteId => $data) {

                $record = SectionConfigRecord::findOne([
                    'sectionId' => $sectionId,
                    'siteId' => $siteId,
                ]);

                if (!$record) {
                    $record = new SectionConfigRecord();
                }

                $record->sectionId = $sectionId;
                $record->siteId = $siteId;

                // create a new model from the existing data
                $sectionConfig = SectionConfig::createFromRecord($record);

                // set the new data
                $entryId = $data['entryId'][0] ?? null;
                $sectionConfig->entryId = $entryId;

                // validate the model
                if (!$sectionConfig->validate()) {
                    Craft::error('Section config is not valid: ' . json_encode($sectionConfig->getErrors()), __METHOD__);
                    continue;
                }

                $record->data = Json::encode($sectionConfig->toArray());

                // save the record
                $record->save();
            }
        }

        return true;
    }

    /**
     * returns all the section configs from the database
     * returns an array of SectionConfig models
     */
    public function getSectionsConfig(): array
    {
        $sections = [];

        // get all the section configs
        $sectionConfigs = SectionConfigRecord::find()->all();

        foreach ($sectionConfigs as $sectionConfig) {
            $sectionId = $sectionConfig->sectionId;
            $siteId = $sectionConfig->siteId;

            $model = SectionConfig::createFromRecord($sectionConfig);

            $sections[$sectionId][$siteId] = $model;
        }

        return $sections;
    }
}
