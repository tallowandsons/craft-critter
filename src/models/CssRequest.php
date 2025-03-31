<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;
use honchoagency\craftcriticalcssgenerator\Critical;

/**
 * Storage Request model
 */
class CssRequest extends Model
{

    private UrlModel $url;

    public function setUrl(UrlModel $url): CssRequest
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): UrlModel
    {
        return $this->url;
    }

    public function getMode(): string
    {
        $defaultMode = Critical::getInstance()->settings->defaultMode;

        if ($defaultMode == Settings::MODE_ENTRY_TYPE) {
            if ($this->url->hasEntryType()) {
                return Settings::MODE_ENTRY_TYPE;
            } else {
                return Settings::MODE_URL;
            }
        }

        return $defaultMode;
    }

    public function getKey(): string
    {
        switch ($this->getMode()) {
            case Settings::MODE_URL:
                return $this->url->getAbsoluteUrl();
            case Settings::MODE_ENTRY_TYPE:
                $entryType = $this->url->getEntryType();
                if ($entryType) {
                    return $entryType;
                }

                return $this->url->getAbsoluteUrl();
            default:
                return $this->url->getAbsoluteUrl();
        }
    }
}
