<?php
namespace ICT\Core\Socket;


/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */


use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use ICT\Core\Api\AuthenticateApi;

class SocketHandler implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        $token = $queryParams['token'] ?? null;
        if($token){
        $auth = new AuthenticateApi();
        $conn->UserData = $auth->tokenPayload($token);
        }
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!isset($data['module'])) {
            $from->send(json_encode(['error' => 'Module not specified']));
            return;
        }
        switch ($data['module']) {
            
            case 'sendfax':
                $this->handleSendfax($from, $data);
            break;
                
            case 'user':
                $this->handleUser($from, $data);
            break;

            default:
                $from->send(json_encode(['error' => 'Invalid module']));
                break;
        }

    } 

    private function handleSendfax(ConnectionInterface $from, $data) {
        $message = $data['message'] ?? 'No message';
        foreach ($this->clients as $client) {
            if (isset($client->UserData)) {
                $userData = $client->UserData;
                if($userData->is_admin == 1 || ($userData->is_tenant == 1 && $data['0']['tenant_id'] == $userData->tenant_id) ||  ($userData->user_id == $data['0']['user_id']) ){
                   $client->send(json_encode($data));
                   echo "Data Sent to Client {$client->resourceId}.\n";
                }
            } else {
                echo "No UserData for client {$client->resourceId}.\n";
            }
        }
    }

    private function handleUser(ConnectionInterface $from, $data) {
        foreach ($this->clients as $client) {
       if (isset($client->UserData)) {
                $userData = $client->UserData;
                if($userData->is_admin == 1 || ($userData->is_tenant == 1 && $data['0']['tenant_id'] == $userData->tenant_id) ||  ($userData->user_id == $data['0']['user_id']) ){
                   $client->send(json_encode($data));
                   echo "Data Sent to Client {$client->resourceId}.\n";
                }
            } else {
                echo "No UserData for client {$client->resourceId}.\n";
            }
  
      }
    }


    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

