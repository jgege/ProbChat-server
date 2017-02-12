<?php
namespace app\components;

use yii\helpers\Json;
use Yii;
use app\models\Chat;
use app\models\ChatSession;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ProbChat implements MessageComponentInterface {
    protected $clients;
    protected $chatSessions = [];
    protected $userChatSessionRegistry = [];

    public function __construct() {
        echo '### ProbChatServer ###' . PHP_EOL;
        echo 'Listening on port: ' . Yii::$app->params['chatServer']['port'];
        $this->clients = new \SplObjectStorage;
        $problemList = Chat::getProblemCategories();
        foreach ($problemList as $key => $problem) {
            $this->chatSessions[$key] = [];
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param type $msg
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $jsonMsg = null;
        try {
            $jsonMsg = Json::decode($msg);
        } catch (\yii\base\Exception $e) {
            echo 'Error: ' . $from->resourceId . ' -> ' . $e->getMessage() . PHP_EOL;
            return;
        }

        $this->chooseAction($from, $jsonMsg);

        /*$numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }*/
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function chooseAction(ConnectionInterface $user, $jsonMsg)
    {
        switch ($jsonMsg['action']) {
            case 'matching':
                $this->matchUser($user, $jsonMsg['problem']);
                break;
            case 'message':
                $message = $this->storeMessage($user, $jsonMsg['msg']);
                $this->sendMessageToPartner($user, $message);
                break;
            case 'message_update':
                $this->updateMessage($user);
                break;
            default:
                break;
        }
    }

    private function matchUser(ConnectionInterface $user, $problemCategory)
    {
        $chatSessionList = $this->chatSessions[$problemCategory];
        foreach ($chatSessionList as &$chatSession) {
            if ($chatSession->hasFreeSpace()) {
                $chatSession->addUser($user);
                $this->addToUserChatSessionRegistry($user, $chatSession);
                $user->send(Json::encode(['action' => 'match']));
                return;
            }
        }
        unset($chatSession);

        $chatSession = new ChatSession();
        $chatSession->addUser($user);
        $this->chatSessions[$problemCategory][] = $chatSession;
        $this->addToUserChatSessionRegistry($user, $chatSession);
        $user->send(Json::encode(['action' => 'no_match']));
    }

    private function sendMessageToPartner(ConnectionInterface $user, $message)
    {
        $chatSession = $this->getChatSessionByUser($user);
        $others = $chatSession->getEveryoneExcludingThisUser($user);
        foreach ($others as $client) {
            $client->send(Json::encode([
                'action' => 'message',
                'msgs' => [
                    [
                        'msg' => $message,
                        'timestamp' => time(),
                        'id' => 42,
                    ]
                ]
            ]));
        }
    }

    private function storeMessage(ConnectionInterface $user, $message)
    {
        return $message;
    }

    private function updateMessage(ConnectionInterface $user)
    {

    }

    private function getChatSessionByUser(ConnectionInterface $user)
    {
        return $this->userChatSessionRegistry[$user->resourceId];
    }

    private function addToUserChatSessionRegistry(ConnectionInterface $user, $chatSession)
    {
        $this->userChatSessionRegistry[$user->resourceId] = $chatSession;
    }
}