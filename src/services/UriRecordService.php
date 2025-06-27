<?php

namespace mijewe\craftcriticalcssgenerator\services;

use craft\helpers\Json;
use DateTime;
use mijewe\craftcriticalcssgenerator\models\UrlModel;
use mijewe\craftcriticalcssgenerator\records\UriRecord;
use yii\base\Component;

/**
 * Uri Service service
 */
class UriRecordService extends Component
{

    public function getRecordByUrl(UrlModel $url): ?UriRecord
    {
        $uri = $url->getRelativeUrl();
        $siteId = $url->siteId;

        return UriRecord::find()
            ->where(['uri' => $uri, 'siteId' => $siteId])
            ->one();
    }

    public function getOrCreateRecord(UrlModel $url): UriRecord
    {
        $record = $this->getRecordByUrl($url);

        if (!$record) {
            $record = new UriRecord();
            $record->uri = $url->getRelativeUrl();
            $record->siteId = $url->siteId;
            $record->status = UriRecord::STATUS_TODO;
        }

        return $record;
    }

    public function createRecordIfNotExists(UrlModel $url): UriRecord
    {
        return $this->getOrCreateRecord($url);
    }

    public function setStatus(UrlModel $url, string $status): UriRecord
    {
        $record = $this->getOrCreateRecord($url);
        $record->status = $status;
        $record->save();
        return $record;
    }

    public function setData(UrlModel $url, array $data): UriRecord
    {
        $record = $this->getOrCreateRecord($url);
        $record->data = Json::encode($data);
        $record->save();
        return $record;
    }

    public function createOrUpdateRecord(UrlModel $url, ?string $status = null, ?array $data = [], ?DateTime $dateQueued = null, ?DateTime $dateGenerated = null, ?DateTime $expiryDate = null): bool
    {
        $record = $this->getOrCreateRecord($url);

        if ($status) {
            $record->status = $status;
        }

        if ($data) {
            $record->data = Json::encode($data);
        }

        if ($dateQueued) {
            $record->dateQueued = $dateQueued;
        }

        if ($dateGenerated) {
            $record->dateGenerated = $dateGenerated;
        }

        if ($expiryDate) {
            $record->expiryDate = $expiryDate;
        }

        return $record->save();
    }
}
