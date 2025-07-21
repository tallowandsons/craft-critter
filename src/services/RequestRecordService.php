<?php

namespace mijewe\critter\services;

use craft\helpers\Json;
use DateTime;
use mijewe\critter\models\CssRequest;
use mijewe\critter\models\UrlModel;
use mijewe\critter\records\RequestRecord;
use yii\base\Component;

/**
 * Request Record Service
 */
class RequestRecordService extends Component
{

    public function getRecordByCssRequest(CssRequest $cssRequest): ?RequestRecord
    {
        $url = $cssRequest->getUrl();
        $uri = $url->getRelativeUrl();
        $siteId = $url->siteId;

        return RequestRecord::find()
            ->where(['uri' => $uri, 'siteId' => $siteId])
            ->one();
    }

    public function getRecordByUrl(UrlModel $url): ?RequestRecord
    {
        $uri = $url->getRelativeUrl();
        $siteId = $url->siteId;

        return RequestRecord::find()
            ->where(['uri' => $uri, 'siteId' => $siteId])
            ->one();
    }

    public function getOrCreateRecord(CssRequest $cssRequest): RequestRecord
    {
        $record = $this->getRecordByCssRequest($cssRequest);

        if (!$record) {
            $url = $cssRequest->getUrl();
            $record = new RequestRecord();
            $record->uri = $url->getRelativeUrl();
            $record->siteId = $url->siteId;
            $record->tag = $cssRequest->getTag();
            $record->status = RequestRecord::STATUS_TODO;
        }

        return $record;
    }

    public function getOrCreateRecordByUrl(UrlModel $url): RequestRecord
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

    public function createRecordIfNotExists(CssRequest $cssRequest): RequestRecord
    {
        return $this->getOrCreateRecord($cssRequest);
    }

    public function setStatus(CssRequest $cssRequest, string $status): RequestRecord
    {
        $record = $this->getOrCreateRecord($cssRequest);
        $record->status = $status;
        $record->save();
        return $record;
    }

    public function setData(CssRequest $cssRequest, array $data): RequestRecord
    {
        $record = $this->getOrCreateRecord($cssRequest);
        $record->data = Json::encode($data);
        $record->save();
        return $record;
    }

    public function createOrUpdateRecord(CssRequest $cssRequest, ?string $status = null, ?array $data = [], ?DateTime $dateQueued = null, ?DateTime $dateGenerated = null, ?DateTime $expiryDate = null): bool
    {
        $record = $this->getOrCreateRecord($cssRequest);

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

        // Tag is automatically set from CssRequest
        $record->tag = $cssRequest->getTag();

        return $record->save();
    }

    /**
     * Expire a record by setting its expiry date to now
     */
    public function expireRecord(CssRequest $cssRequest): bool
    {
        $record = $this->getRecordByCssRequest($cssRequest);

        if (!$record) {
            // No record exists, nothing to expire
            return true;
        }

        // Don't update expiry date if it's already set
        if ($record->expiryDate !== null) {
            return true;
        }

        $record->expiryDate = new DateTime();
        return $record->save();
    }

    /**
     * Set a custom expiry date for a record
     */
    public function setExpiryDate(CssRequest $cssRequest, DateTime $expiryDate): bool
    {
        return $this->createOrUpdateRecord($cssRequest, null, [], null, null, $expiryDate);
    }

    /**
     * Expire all records with a specific tag
     */
    public function expireRecordsByTag(string $tag): bool
    {
        try {
            $records = RequestRecord::find()
                ->where(['tag' => $tag])
                ->andWhere(['expiryDate' => null]) // Only select records that don't already have an expiry date
                ->all();

            $success = true;
            foreach ($records as $record) {
                $record->expiryDate = new DateTime();
                if (!$record->save()) {
                    $success = false;
                }
            }

            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Set tag for a record
     */
    public function setRelatedTag(CssRequest $cssRequest, string $tag): bool
    {
        $record = $this->getOrCreateRecord($cssRequest);
        $record->tag = $tag;
        return $record->save();
    }
}
