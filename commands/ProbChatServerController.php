<?php namespace app\commands;

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
                ), 8080
        );
        $server->run();

        exit;
    }
}
