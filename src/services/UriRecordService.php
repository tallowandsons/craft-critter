<?php

namespace honchoagency\craftcriticalcssgenerator\services;

use craft\helpers\Json;
use DateTime;
use honchoagency\craftcriticalcssgenerator\models\UrlModel;
use honchoagency\craftcriticalcssgenerator\records\UriRecord;
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

    public function saveOrUpdateUrl(UrlModel $url, ?string $status = null, ?array $data = [], ?DateTime $dateQueued = null, ?DateTime $dateGenerated = null, ?DateTime $expiryDate = null): bool
    {

        $uri = $url->getRelativeUrl();
        $siteId = $url->siteId;

        $record = UriRecord::find()
            ->where(['uri' => $uri, 'siteId' => $siteId])
            ->one();

        if (!$record) {
            $record = new UriRecord();
            $record->uri = $uri;
            $record->siteId = $siteId;
        }

        $record->status = $status;
        $record->data = Json::encode($data ?? []);
        $record->dateQueued = $dateQueued;
        $record->dateGenerated = $dateGenerated;
        $record->expiryDate = $expiryDate;

        return $record->save();
    }
}
