<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use honchoagency\craftcriticalcssgenerator\Critical;

/**
 * Url Model model
 */
class UrlModel extends Model
{
    public ?string $url = '';
    public ?int $siteId = null;
    public array $queryParams = [];

    public function __construct(?string $url = null, ?int $siteId = null)
    {
        $this->siteId = $siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        $this->url = $url;
    }

    public function getUrl(): string
    {

        $baseUrlOverride = Critical::getInstance()->settings->baseUrlOverride ?? null;
        if ($baseUrlOverride) {
            $baseUrlOverride = rtrim($baseUrlOverride, '/');
        }

        if ($this->url === Element::HOMEPAGE_URI) {
            $url = UrlHelper::siteUrl('', $this->queryParams, null, $this->siteId);
        }

        $url = UrlHelper::siteUrl($this->url, $this->queryParams, null, $this->siteId);

        if ($baseUrlOverride) {
            $siteBaseUrl = rtrim(Craft::$app->sites->getSiteById($this->siteId)->baseUrl, '/');
            $url = str_replace($siteBaseUrl, $baseUrlOverride, $url);
        }

        return $url;
    }

    public function getRelativeUrl(): string
    {
        $url = $this->getUrl();
        $url = UrlHelper::rootRelativeUrl($url);
        return $url;
    }

    public function getAbsoluteUrl(): string
    {
        return $this->getUrl();
    }

    public function getSafeUrl()
    {
        return $this->getUrl();
    }

    /**
     * returns the path for the url without the query string
     * eg the path for https://website.com/about/team?foo=bar is "about/team"
     */
    public function getPath(): string
    {
        $url = $this->getRelativeUrl();
        $url = UrlHelper::stripQueryString($url);
        $url = trim($url, '/');
        return $url;
    }

    /**
     * returns the 'matched element' for the url (if it exists)
     * ie the Entry associated with the url
     */
    public function getMatchedElement(): ?ElementInterface
    {
        $url = $this->getPath();
        $siteId = $this->siteId;

        return Craft::$app->getElements()->getElementByUri($url, null, $siteId);
    }

    /**
     * return the handle of the url's section (if it exists)
     */
    public function getSectionHandle(): ?string
    {
        $element = $this->getMatchedElement();
        if ($element instanceof Entry) {
            return $element->section->handle;
        }
        return null;
    }

    /**
     * returns whether the url has a section
     */
    public function hasSection(): bool
    {
        return $this->getSectionHandle() !== null;
    }

    /**
     * returns the handle of the url's entry type (if it exists)
     */
    public function getEntryTypeHandle(): ?string
    {
        $element = $this->getMatchedElement();
        if ($element instanceof Entry) {
            return $element->getType()->handle;
        }
        return null;
    }

    /**
     * returns whether the url has an entry type
     */
    public function hasEntryType(): bool
    {
        return $this->getEntryTypeHandle() !== null;
    }
}
