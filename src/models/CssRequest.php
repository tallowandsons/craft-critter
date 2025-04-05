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

    /**
     * Each section has a 'mode', which determines whether to
     * generate unique critical css for each url, or once
     * for the entire section.
     * This function returns the mode for the URL.
     */
    public function getMode(): string
    {
        $preferredMode = null;

        if ($this->url->hasSection()) {
            $preferredMode = Critical::getInstance()->settingsService->getSectionMode($this->url->getSectionHandle());
        } else {
            return Settings::MODE_URL;
        }

        // if the section is a single, switch preferred mode to url
        if ($this->url->isSingle()) {
            $preferredMode = Settings::MODE_URL;
        }

        switch ($preferredMode) {

            // section mode is only possible if the url
            // has a matched section. Otherwise,
            // fallback to url mode.
            case Settings::MODE_SECTION:
                if ($this->url->hasSection()) {
                    return Settings::MODE_SECTION;
                } else {
                    return Settings::MODE_URL;
                }
                break;

            // entry type mode is only possible if the url
            // has a matched entry type. Otherwise,
            // fallback to url mode.
            case Settings::MODE_ENTRY_TYPE:
                if ($this->url->hasEntryType()) {
                    return Settings::MODE_ENTRY_TYPE;
                } else {
                    return Settings::MODE_URL;
                }
                break;

            // url mode is always possible
            case Settings::MODE_URL:
                return Settings::MODE_URL;
                break;

            default:
                return Critical::getInstance()->settings->defaultMode;
        }
    }

    public function getKey(): string
    {
        switch ($this->getMode()) {
            case Settings::MODE_SECTION:
                return $this->url->getSectionHandle();
                break;
            case Settings::MODE_ENTRY_TYPE:
                return $this->url->getEntryTypeHandle();
                break;
            case Settings::MODE_URL:
            default:
                return $this->url->getAbsoluteUrl();
        }
    }
}
