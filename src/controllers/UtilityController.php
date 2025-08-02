<?php

namespace tallowandsons\critter\controllers;

use Craft;
use craft\web\Controller;
use tallowandsons\critter\Critter;
use yii\web\Response;

/**
 * Utility controller
 */
class UtilityController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * Expire all Critical CSS records by updating their expiry dates.
     * /action/critter/utility/expire-all
     */
    public function actionExpireAll(): Response
    {
        $this->requirePostRequest();

        $response = Critter::getInstance()->utilityService->expireAll();

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Expire Critical CSS records for a specific entry.
     * /action/critter/utility/expire-entry
     */
    public function actionExpireEntry(): Response
    {
        $this->requirePostRequest();

        $entryIds = $this->request->getBodyParam('entryIds', []);

        if (empty($entryIds)) {
            Craft::$app->getSession()->setError(Critter::translate('No entry selected.'));
            return $this->redirectToPostedUrl();
        }

        // Take the first entry ID if multiple are selected
        $entryId = (int) $entryIds[0];

        $response = Critter::getInstance()->utilityService->expireEntry($entryId);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Expire Critical CSS records for a specific section.
     * /action/critter/utility/expire-section
     */
    public function actionExpireSection(): Response
    {
        $this->requirePostRequest();

        $sectionHandle = $this->request->getBodyParam('sectionHandle');

        if (!$sectionHandle) {
            Craft::$app->getSession()->setError(Critter::translate('No section selected.'));
            return $this->redirectToPostedUrl();
        }

        $response = Critter::getInstance()->utilityService->expireSection($sectionHandle);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Clear stuck CSS generation records.
     * /action/critter/utility/clear-stuck
     */
    public function actionClearStuck(): Response
    {
        $this->requirePostRequest();

        $response = Critter::getInstance()->utilityService->clearStuckRecords();

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Regenerate all expired Critical CSS records.
     * /action/critter/utility/regenerate-expired
     */
    public function actionRegenerateExpired(): Response
    {
        $this->requirePostRequest();

        $response = Critter::getInstance()->utilityService->regenerateExpired();

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Regenerate Critical CSS records for a specific entry.
     * /action/critter/utility/regenerate-entry
     */
    public function actionRegenerateEntry(): Response
    {
        $this->requirePostRequest();

        $entryIds = $this->request->getBodyParam('entryIds', []);

        if (empty($entryIds)) {
            Craft::$app->getSession()->setError(Critter::translate('No entry selected.'));
            return $this->redirectToPostedUrl();
        }

        // Take the first entry ID if multiple are selected
        $entryId = (int) $entryIds[0];

        $response = Critter::getInstance()->utilityService->regenerateEntry($entryId);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Regenerate Critical CSS records for a specific section.
     * /action/critter/utility/regenerate-section
     */
    public function actionRegenerateSection(): Response
    {
        $this->requirePostRequest();

        $sectionHandle = $this->request->getBodyParam('sectionHandle');

        if (!$sectionHandle) {
            Craft::$app->getSession()->setError(Critter::translate('No section selected.'));
            return $this->redirectToPostedUrl();
        }

        $response = Critter::getInstance()->utilityService->regenerateSection($sectionHandle);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Regenerate all Critical CSS records.
     * /action/critter/utility/regenerate-all
     */
    public function actionRegenerateAll(): Response
    {
        $this->requirePostRequest();

        $response = Critter::getInstance()->utilityService->regenerateAll();

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Clear the Critter cache.
     * This will clear all cached CSS and related data.
     * /action/critter/utility/clear-cache
     */
    public function actionClearCache(): Response
    {
        $this->requirePostRequest();

        $response = Critter::getInstance()->storage->clearAll();

        if ($response) {
            Craft::$app->getSession()->setNotice(Critter::translate('Cache cleared successfully.'));
        } else {
            Craft::$app->getSession()->setError(Critter::translate('Failed to clear cache.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Generate fallback CSS from the configured entry.
     * /action/critter/utility/generate-fallback-css
     */
    public function actionGenerateFallbackCss(): Response
    {
        $this->requirePostRequest();

        // Get selected site IDs from the form
        $siteIds = $this->request->getBodyParam('siteIds', []);

        if (empty($siteIds)) {
            Craft::$app->getSession()->setError(Critter::translate('Please select at least one site to generate fallback CSS for.'));
            return $this->redirectToPostedUrl();
        }

        // Ensure site IDs are integers
        $siteIds = array_map('intval', $siteIds);

        $response = Critter::getInstance()->utilityService->generateFallbackCss($siteIds);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Clear generated fallback CSS.
     * /action/critter/utility/clear-generated-fallback-css
     */
    public function actionClearGeneratedFallbackCss(): Response
    {
        $this->requirePostRequest();

        // Get site IDs from the request
        $siteIds = $this->request->getBodyParam('siteIds', []);

        // Ensure we have an array of integers
        if (!is_array($siteIds)) {
            $siteIds = [];
        } else {
            $siteIds = array_map('intval', array_filter($siteIds, 'is_numeric'));
        }

        // If no site IDs provided, find all sites that have generated fallback CSS files
        if (empty($siteIds)) {
            $siteIds = Critter::getInstance()->fallbackService->getSitesWithGeneratedFallbackCss();
        }

        // Validate that we have sites to process
        if (empty($siteIds)) {
            Craft::$app->getSession()->setNotice(Critter::translate('No generated fallback CSS files found to clear.'));
            return $this->redirectToPostedUrl();
        }

        $response = Critter::getInstance()->utilityService->clearGeneratedFallbackCss($siteIds);

        if ($response->isSuccess()) {
            Craft::$app->getSession()->setNotice($response->getMessage());
        } else {
            Craft::$app->getSession()->setError($response->getMessage());
        }

        return $this->redirectToPostedUrl();
    }
}
