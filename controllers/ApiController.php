<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Chat;

class ApiController extends Controller
{

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return true;
        }

        return false;
    }

    public function actionProblemCategories()
    {
        $problemList = Chat::getProblemCategories();
        $response = [];
        foreach ($problemList as $index => $problem) {
            $response[] = [
                'id' => $index,
                'caption' => $problem
            ];
        }
        return ['results' => $response];
    }

}
