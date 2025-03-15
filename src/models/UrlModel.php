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

    public function __construct(?string $url, ?int $siteId = null)
    {
        $this->siteId = $siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        $this->url = $url;
    }

    public function getUrl(array $params = []): string
    {
        if ($this->url === Element::HOMEPAGE_URI) {
            return UrlHelper::siteUrl('', $params, null, $this->siteId);
        }

        return UrlHelper::siteUrl($this->url, $params, null, $this->siteId);
    }

    public function getSafeUrl()
    {
        $url = $this->url;
        return $url;
    }
}
