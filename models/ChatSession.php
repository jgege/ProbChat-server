<?php

namespace app\models;
use Ratchet\ConnectionInterface;

class ChatSession extends \yii\base\Model
{
    public $problem;
    private $_slots = 2;
    private $_connectedClients;

    public function hasUser(ConnectionInterface $client) {
        foreach ($this->_connectedClients as $member) {
            return ($member == $client);
        }
        return false;
    }

    public function getUserCount() {
        return count($this->_connectedClients);
    }

    public function hasFreeSpace() {
        return ($this->getUserCount() < $this->_slots);
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

    public function userInAlready($user) {
        foreach ($this->_connectedClients as $member) {
            if ($member == $user) {
                return true;
            }
        }

        return false;
    }

    public function testIds() {
        $idList = '';
        foreach ($this->_connectedClients as $member) {
            $idList .= $member->resourceId . ' - ';
        }
        return $idList;
    }
}
