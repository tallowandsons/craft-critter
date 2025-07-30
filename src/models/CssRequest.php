<?php

namespace tallowandsons\critter\models;

use Craft;
use craft\base\Model;
use craft\elements\Entry;
use tallowandsons\critter\Critter;
use tallowandsons\critter\factories\UrlFactory;
use tallowandsons\critter\models\Tag;

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
     * get the URL that was originally requested
     */
    public function getRequestUrl(): UrlModel
    {
        return $this->requestUrl;
    }

    /**
     * get the URL that will be used to generate the critical css
     */
    public function getUrl(): UrlModel
    {
        if ($this->getMode() == Settings::MODE_SECTION) {
            $section = $this->requestUrl->getSection();
            $sectionConfig = Critter::getInstance()->configService->getSectionConfig($section->id, $this->requestUrl->getSiteId());
            $generateEntry = $sectionConfig ? $sectionConfig->getEntry() : null;

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
     * generate unique critical css for each entry, or once
     * for the entire section.
     * This function returns the mode for the URL.
     */
    public function getMode(): string
    {
        $preferredMode = null;

        if ($this->requestUrl->hasSection()) {
            $preferredMode = Critter::getInstance()->settingsService->getSectionMode($this->requestUrl->getSectionHandle());
        } else {
            return Settings::MODE_ENTRY;
        }

        // if the section is a single, switch preferred mode to entry
        if ($this->requestUrl->isSingle()) {
            $preferredMode = Settings::MODE_ENTRY;
        }

        switch ($preferredMode) {

            // section mode is only possible if the url
            // has a matched section. Otherwise,
            // fallback to entry mode.
            case Settings::MODE_SECTION:
                if ($this->requestUrl->hasSection()) {
                    return Settings::MODE_SECTION;
                } else {
                    return Settings::MODE_ENTRY;
                }
                break;

            // entry mode is always possible
            case Settings::MODE_ENTRY:
                return Settings::MODE_ENTRY;
                break;

            default:
                return Critter::getInstance()->settings->defaultMode;
        }
    }

    public function getKey(): string
    {
        switch ($this->getMode()) {
            case Settings::MODE_SECTION:
                return 'mode:section|site:' . $this->requestUrl->getSiteId() . '|section:' . $this->requestUrl->getSectionHandle() . '|query:' . $this->requestUrl->getQueryString();
                break;
            case Settings::MODE_ENTRY:
            default:
                // Use entry ID in cache key instead of full URL to maintain cache across URI changes
                $entry = $this->requestUrl->getMatchedElement();
                if ($entry instanceof Entry) {
                    return 'mode:entry|site:' . $this->requestUrl->getSiteId() . '|entry:' . $entry->id . '|query:' . $this->requestUrl->getQueryString();
                } else {
                    // Fallback for non-entry URLs
                    return $this->requestUrl->getAbsoluteUrl();
                }
        }
    }

    /**
     * Get the tag for this request (used for expiration grouping)
     */
    public function getTag(): string
    {
        switch ($this->getMode()) {
            case Settings::MODE_SECTION:
                return 'section:' . $this->requestUrl->getSectionHandle();
            case Settings::MODE_ENTRY:
            default:
                // For entry mode, we need the entry ID from the URL
                $entry = $this->requestUrl->getMatchedElement();
                if ($entry instanceof Entry) {
                    return Tag::fromEntry($entry)->toString();
                } else {
                    // Fallback for non-entry URLs
                    return 'url:' . md5($this->requestUrl->getAbsoluteUrl());
                }
        }
    }

    static public function createFromEntry(Entry $entry): self
    {
        $cssRequest = new self();
        $cssRequest->setRequestUrl(UrlFactory::createFromEntry($entry));
        return $cssRequest;
    }
}
