<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Element;
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

    public function getPath()
    {
        $url = $this->getRelativeUrl();
        $url = UrlHelper::stripQueryString($url);
        $url = trim($url, '/');
        return $url;
    }

    public function getMatchedElement()
    {
        $url = $this->getPath();
        $siteId = $this->siteId;

        return Craft::$app->getElements()->getElementByUri($url, null, $siteId);
    }

    public function getEntryType()
    {
        $element = $this->getMatchedElement();
        if ($element instanceof Entry) {
            return $element->getType()->handle;
        }

        return null;
    }
}
