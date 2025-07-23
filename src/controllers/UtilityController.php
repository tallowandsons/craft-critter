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
}
