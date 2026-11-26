<?php

require '/usr/ictcore/vendor/autoload.php'; 

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ICT\Core\Socket\SocketHandler;

$SocketHandlerServer = new SocketHandler();
$server = IoServer::factory(
    new HttpServer(
        new WsServer($SocketHandlerServer)
    ),
    8080
);
echo "WebSocket server running on ws://localhost:8080\n";
$server->run();
