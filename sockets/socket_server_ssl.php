<?php

require '/usr/ictcore/vendor/autoload.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use ICT\Core\Socket\SocketHandler;

$app = new HttpServer(
   new WsServer(
      new SocketHandler()
   )
);

$loop = Factory::create();
$secure_websockets = new React\Socket\SocketServer('127.0.0.1:8080', [
    'local_cert' => '/etc/letsencrypt/live/demo.ictfax.com/fullchain.pem', // path to your cert
    'local_pk' => '/etc/letsencrypt/live/demo.ictfax.com/privkey.pem', // path to your server private key
            'allow_self_signed' => TRUE, // Allow self signed certs (should be false in production)
            'verify_peer' => FALSE 
], $loop);

$secure_websockets_server = new IoServer($app, $secure_websockets, $loop);
$secure_websockets_server->run();
