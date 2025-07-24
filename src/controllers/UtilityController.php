<?php

namespace mijewe\critter\controllers;

use Craft;
use craft\web\Controller;
use mijewe\critter\Critter;
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
}
