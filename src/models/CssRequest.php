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

    // the url that was originally requested
    private UrlModel $requestUrl;

    public function setRequestUrl(UrlModel $url): CssRequest
    {
        $this->requestUrl = $url;
        return $this;
    }

    /**
     * get the URL that will be used to generate the critical css
     */
    public function getUrl(): UrlModel
    {
        if ($this->getMode() == Settings::MODE_SECTION) {
            $section = $this->requestUrl->getSection();
            $sectionConfig = Critical::getInstance()->configService->getSectionConfig($section->id, $this->requestUrl->getSiteId());
            $generateEntry = $sectionConfig->getEntry();

            // if there is no specific entry for the section,
            // use the url as the generate url
            if ($generateEntry === null) {
                return $this->requestUrl;
            }

            return (new UrlModel($generateEntry->getUrl(), $this->requestUrl->getSiteId()))
                ->setQueryParams($this->requestUrl->queryParams);
        }

        return $this->requestUrl;
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

        if ($this->requestUrl->hasSection()) {
            $preferredMode = Critical::getInstance()->settingsService->getSectionMode($this->requestUrl->getSectionHandle());
        } else {
            return Settings::MODE_URL;
        }

        // if the section is a single, switch preferred mode to url
        if ($this->requestUrl->isSingle()) {
            $preferredMode = Settings::MODE_URL;
        }

        switch ($preferredMode) {

            // section mode is only possible if the url
            // has a matched section. Otherwise,
            // fallback to url mode.
            case Settings::MODE_SECTION:
                if ($this->requestUrl->hasSection()) {
                    return Settings::MODE_SECTION;
                } else {
                    return Settings::MODE_URL;
                }
                break;

            // entry type mode is only possible if the url
            // has a matched entry type. Otherwise,
            // fallback to url mode.
            case Settings::MODE_ENTRY_TYPE:
                if ($this->requestUrl->hasEntryType()) {
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
                return $this->requestUrl->getSectionHandle() . $this->requestUrl->getQueryString();
                break;
            case Settings::MODE_ENTRY_TYPE:
                return $this->requestUrl->getEntryTypeHandle();
                break;
            case Settings::MODE_URL:
            default:
                return $this->requestUrl->getAbsoluteUrl();
        }
    }
}
