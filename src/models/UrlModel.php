<?php

namespace mijewe\critter\models;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\models\Section;
use mijewe\critter\Critter;

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

        $baseUrlOverride = App::parseEnv(Critter::getInstance()->settings->baseUrlOverride ?? null);
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

    public function getSiteId()
    {
        return $this->siteId;
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
     * returns the domain for the url
     * eg the domain for https://website.com/about/team?foo=bar is "website.com"
     * returns null if the url is not valid or does not have a domain
     */
    public function getDomain(): ?string
    {
        $url = $this->getUrl();
        $parsedUrl = parse_url($url);
        return $parsedUrl['host'] ?? null;
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
     * return a url's section (if it exists)
     */
    public function getSection(): ?Section
    {
        $element = $this->getMatchedElement();
        if ($element instanceof Entry) {
            return $element->section;
        }
        return null;
    }

    /**
     * return the handle of the url's section (if it exists)
     */
    public function getSectionHandle(): ?string
    {
        $section = $this->getSection();
        if ($section) {
            return $section->handle;
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
     * returns the type of the url's section (if it exists)
     * ie 'single', 'channel', or 'structure' (or null)
     */
    public function getSectionType(): ?string
    {
        $sectionHandle = $this->getSectionHandle();
        if (!$sectionHandle) {
            return null;
        }

        $section = Craft::$app->entries->getSectionByHandle($sectionHandle);
        if (!$section) {
            return null;
        }

        return $section->type;
    }

    /**
     * returns whether the url is a single entry
     */
    public function isSingle(): bool
    {
        return $this->getSectionType() === 'single';
    }

    /**
     * Returns the query parameters for the URL.
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Sets the query parameters for the URL.
     */
    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    /**
     * Returns the query parameters as a query string.
     */
    public function getQueryString()
    {
        $params = http_build_query($this->queryParams);
        return $params ? '?' . $params : '';
    }
}
