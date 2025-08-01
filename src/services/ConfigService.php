<?php

namespace tallowandsons\critter\services;

use Craft;
use craft\helpers\Json;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\SectionConfig;
use tallowandsons\critter\records\SectionConfigRecord;
use tallowandsons\critter\records\ConfigRecord;
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
    public function save(array $config, int $siteId): bool
    {
        $sections = $config['sections'] ?? [];

        $fallbackCssEntryId = null;
        if (isset($config['fallbackCssEntryId'])) {
            if (is_array($config['fallbackCssEntryId']) && !empty($config['fallbackCssEntryId'])) {
                $fallbackCssEntryId = (int) $config['fallbackCssEntryId'][0];
            } elseif (is_numeric($config['fallbackCssEntryId'])) {
                $fallbackCssEntryId = (int) $config['fallbackCssEntryId'];
            }
        }

        $this->saveSections($sections);
        $this->saveFallbackCssEntryId($fallbackCssEntryId, $siteId);

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
    public function getSectionConfigs(): array
    {
        $sections = [];

        // get all the section configs
        $sectionConfigs = SectionConfigRecord::find()->all();

        foreach ($sectionConfigs as $sectionConfig) {
            $sectionId = $sectionConfig->sectionId;
            $siteId = $sectionConfig->siteId;
            $sections[$sectionId][$siteId] = $this->getSectionConfig($sectionId, $siteId);
        }

        return $sections;
    }

    /**
     * returns a section config for a given section and site
     */
    public function getSectionConfig(int $sectionId, int $siteId): ?SectionConfig
    {
        $record = SectionConfigRecord::findOne([
            'sectionId' => $sectionId,
            'siteId' => $siteId,
        ]);

        if (!$record) {
            return null;
        }

        return SectionConfig::createFromRecord($record);
    }

    /**
     * Save the fallback CSS entry ID to config database
     */
    private function saveFallbackCssEntryId(?int $entryId, int $siteId): bool
    {
        $record = ConfigRecord::findOne([
            'key' => 'fallbackCssEntryId',
            'siteId' => $siteId,
        ]);

        if (!$record) {
            $record = new ConfigRecord();
            $record->key = 'fallbackCssEntryId';
            $record->siteId = $siteId;
        }

        $record->value = $entryId ? (string) $entryId : null;

        return $record->save();
    }

    /**
     * Get the fallback CSS entry ID from config database
     */
    public function getFallbackCssEntryId(int $siteId = null): ?int
    {
        $record = ConfigRecord::findOne([
            'key' => 'fallbackCssEntryId',
            'siteId' => $siteId,
        ]);

        if (!$record || !$record->value) {
            return null;
        }

        return (int) $record->value;
    }
}
