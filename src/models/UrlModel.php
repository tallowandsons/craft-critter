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
    private ?string $url = '';
    private ?int $siteId = null;
    private array $queryParams = [];

    public function __construct(?string $url = null, ?int $siteId = null)
    {
        $this->setSiteId($siteId ?? Craft::$app->getSites()->getCurrentSite()->id);
        if ($url) {
            $this->setUrl($url);
        }
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
        $this->queryParams = $this->getFilteredQueryParams($queryParams);
        return $this;
    }

    /**
     * Sets the URL and automatically extracts query parameters
     */
    public function setUrl(string $url): self
    {
        // Use Craft's UrlHelper to cleanly separate URL from query string
        $this->url = UrlHelper::stripQueryString($url);

        // Extract query parameters if they exist
        if (str_contains($url, '?')) {
            $queryString = substr($url, strpos($url, '?') + 1);
            parse_str($queryString, $allQueryParams);
            $this->queryParams = $this->getFilteredQueryParams($allQueryParams);
        } else {
            $this->queryParams = [];
        }

        return $this;
    }

    /**
     * Sets the site ID
     */
    public function setSiteId(int $siteId): self
    {
        $this->siteId = $siteId;
        return $this;
    }

    /**
     * Gets the raw URL (path only, without query string)
     */
    public function getRawUrl(): string
    {
        return $this->url ?? '';
    }

    /**
     * Returns the query parameters as a query string (without leading ?).
     */
    public function getQueryString()
    {
        return UrlHelper::buildQuery($this->queryParams);
    }

    /**
     * Filter query parameters based on uniqueQueryParams settings
     */
    private function getFilteredQueryParams(array $queryParams): array
    {
        try {
            $settings = Critter::getInstance()->settings ?? null;
            if (!$settings) {
                // If no settings available, return all params
                return $queryParams;
            }

            $uniqueQueryParamsSettings = $settings->uniqueQueryParams ?? [];

            // Extract enabled parameter names
            $enabledParams = [];
            foreach ($uniqueQueryParamsSettings as $setting) {
                $isEnabled = !empty($setting['enabled']) && filter_var($setting['enabled'], FILTER_VALIDATE_BOOLEAN);
                if ($isEnabled && !empty($setting['param'])) {
                    $enabledParams[] = $setting['param'];
                }
            }

            // If no parameters are configured as enabled, return all params
            if (empty($enabledParams)) {
                return $queryParams;
            }

            // Keep only allowed query string params
            return array_intersect_key($queryParams, array_flip($enabledParams));
        } catch (\Exception $e) {
            // If settings aren't available, return all params
            return $queryParams;
        }
    }
}
