<?php
namespace ICT\Core\Socket;

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use ICT\Core\Corelog;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

 class SocketMessage {

    public static function Message($socket_data = array()){
      $connector = new Connector();
      $connector('ws://127.0.0.1:8080')->then(
          function (WebSocket $conn) use ($socket_data) {
              $json_data = json_encode($socket_data);
              if ($conn->send($json_data)) {
                  Corelog::log("Socket Data sent successfully", Corelog::ERROR);
              } else {
                  Corelog::log("Failed to send data to WebSocket server", Corelog::ERROR);
              }
              $conn->close();
          },
          function ($e) {
              Corelog::log("Failed to connect to WebSocket server: " . $e->getMessage(), Corelog::ERROR);
          }
      );
      $connector('ws://127.0.0.1:8090')->then(
          function (WebSocket $conn) use ($socket_data) {
              $json_data = json_encode($socket_data);
              if ($conn->send($json_data)) {
                  Corelog::log("Socket Data sent successfully", Corelog::ERROR);
              } else {
                  Corelog::log("Failed to send data to WebSocket server", Corelog::ERROR);
              }
              $conn->close();
          },
          function ($e) {
              Corelog::log("Failed to connect to WebSocket server: " . $e->getMessage(), Corelog::ERROR);
          }
);
    }
 }


?>
