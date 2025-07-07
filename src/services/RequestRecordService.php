<?php

namespace mijewe\critter\services;

use craft\helpers\Json;
use DateTime;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;
use yii\base\Component;

/**
 * Request Record Service
 */
class RequestRecordService extends Component
{

    public function getRecordByUrl(UrlModel $url): ?RequestRecord
    {
        $uri = $url->getRelativeUrl();
        $siteId = $url->siteId;

        return RequestRecord::find()
            ->where(['uri' => $uri, 'siteId' => $siteId])
            ->one();
    }

    public function getOrCreateRecord(UrlModel $url): RequestRecord
    {
        $record = $this->getRecordByUrl($url);

        if (!$record) {
            $record = new RequestRecord();
            $record->uri = $url->getRelativeUrl();
            $record->siteId = $url->siteId;
            $record->status = RequestRecord::STATUS_TODO;
        }

        return $record;
    }

    public function createRecordIfNotExists(UrlModel $url): RequestRecord
    {
        return $this->getOrCreateRecord($url);
    }

    public function setStatus(UrlModel $url, string $status): RequestRecord
    {
        $record = $this->getOrCreateRecord($url);
        $record->status = $status;
        $record->save();
        return $record;
    }

    public function setData(UrlModel $url, array $data): RequestRecord
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
