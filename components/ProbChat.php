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
        echo 'Listening on port: ' . Yii::$app->params['chatServer']['port'] . PHP_EOL;
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
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->disconnectUser($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    private function disconnectUser(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->removeFromSessions($conn);
    }

    private function removeFromSessions(ConnectionInterface $conn)
    {
        $chatSession = $this->getChatSessionByUser($conn);
        $chatPartnerList = $chatSession->getEveryone();
        if (!$chatSession->removeUser($conn)) {
            echo 'Error on removing user from chat session' . PHP_EOL;
        }
        foreach ($chatPartnerList as $partner) {
            if (count($chatPartnerList) < 2) {
                $this->removeFromUserChatSessionRegistry($partner);
            }
            echo 'Removing: ' . $partner->resourceId . ' - ' . $conn->resourceId . PHP_EOL;
            if ($partner->resourceId != $conn->resourceId) {
                $partner->send(Json::encode([
                    'action' => 'partner_disconnected',
                    'chatSessionUserCount' => $chatSession->getUserCount(),
                ]));
            }
        }
        $this->removeFromUserChatSessionRegistry($conn);
        if ($chatSession->getUserCount() < 2) {
            $this->removeChatSession($chatSession);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function chooseAction(ConnectionInterface $user, $jsonMsg)
    {
        switch ($jsonMsg['action']) {
            case 'matching':
                $this->matchUser($user, $jsonMsg['id']);
                break;
            case 'message':
                $message = $this->storeMessage($user, $jsonMsg['msg']);
                $this->sendMessageToPartner($user, $message);
                break;
            case 'message_update':
                $this->updateMessage($user);
                break;
            case 'quit':
                $this->removeFromSessions($user);
                break;
            case 'disconnect':
                $this->disconnectUser($user);
                $user->close();
                break;
            default:
                break;
        }
    }

    private function matchUser(ConnectionInterface $user, $problemCategory)
    {
        $chatSessionList = $this->chatSessions[$problemCategory];
        foreach ($chatSessionList as &$chatSession) {
            if (!$chatSession->userInAlready($user) && $chatSession->hasFreeSpace()) {
                $chatSession->addUser($user);
                $this->addToUserChatSessionRegistry($user, $chatSession);

                if (!$chatSession->hasFreeSpace()) {
                    foreach($chatSession->getEveryone() as $member) {
                        $member->send(Json::encode(['action' => 'match']));
                    }
                }
                return;
            }
        }
        unset($chatSession);

        $chatSession = new ChatSession();
        $chatSession->problem = $problemCategory;
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

    private function removeFromUserChatSessionRegistry(ConnectionInterface $user)
    {
        unset($this->userChatSessionRegistry[$user->resourceId]);
    }

    private function removeChatSession(ChatSession $chatSession) {
        foreach ($this->chatSessions[$chatSession->problem] as $id => $session) {
            if ($session == $chatSession) {
                unset($this->chatSessions[$chatSession->problem][$id]);
                return;
            }
        }
    }
}