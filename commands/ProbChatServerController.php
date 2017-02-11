<?php namespace app\commands;

use Yii;
use app\components\ProbChat;
use yii\console\Controller;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ProbChatServerController extends Controller
{

    public function actionIndex()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ProbChat()
                )
            ),
            Yii::$app->params['chatServer']['port']
        );
        $server->run();

        exit;
    }
}
