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
}
