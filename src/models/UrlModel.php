<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\helpers\UrlHelper;

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
        if ($this->url === Element::HOMEPAGE_URI) {
            return UrlHelper::siteUrl('', $this->queryParams, null, $this->siteId);
        }

        return UrlHelper::siteUrl($this->url, $this->queryParams, null, $this->siteId);
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
}
