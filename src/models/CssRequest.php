<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;

/**
 * Storage Request model
 */
class CssRequest extends Model
{

    public UrlModel $url;
    public string $mode;

    public function setUrl(UrlModel $url): CssRequest
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): UrlModel
    {
        return $this->url;
    }

    public function setMode(string $mode): CssRequest
    {
        $this->mode = $mode;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getKey(): string
    {
        switch ($this->mode) {
            case Settings::MODE_URL:
                return $this->url->getAbsoluteUrl();
            case Settings::MODE_ENTRY_TYPE:
                return $this->url->getEntryType();
            default:
                return $this->url->getAbsoluteUrl();
        }
    }
}
