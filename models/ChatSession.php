<?php

namespace app\models;
use Ratchet\ConnectionInterface;

class ChatSession extends \yii\base\Model
{
    private $_slots = 2;
    private $_connectedClients;

    public function hasUser(ConnectionInterface $client) {
        foreach ($this->_connectedClients as $member) {
            return ($member == $client);
        }
        return false;
    }

    public function hasFreeSpace() {
        return (count($this->_connectedClients) < $this->_slots);
    }

    public function addUser(ConnectionInterface $client) {
        $this->_connectedClients[] = $client;
    }

    public function removeUser($client) {
        foreach ($this->_connectedClients as $index => $member) {
            if ($member == $client) {
                unset($this->_connectedClients[$index]);
                return true;
            }
        }
        return false;
    }

    public function getEveryoneExcludingThisUser($client) {
        $result = [];
        foreach ($this->_connectedClients as $member) {
            if ($member != $client) {
                $result[] = $member;
            }
        }

        return $result;
    }
}
