ICTCore WebSocket Integration Using Ratchet

This README provides a detailed guide to set up and configure Ratchet WebSockets for enabling real-time responses on your website.

===============================================================================

1. Introduction

We are using Ratchet WebSockets to implement real-time communication for our website. This enables dynamic updates without requiring page refreshes.

===============================================================================

2. Installation and Setup

2.1 Install Required Libraries

Install the necessary Ratchet WebSocket libraries using Composer:

composer require cboden/ratchet
composer require ratchet/pawl

===============================================================================

2.2 Verify Directory Structure

Ensure the following directories exist in your project (cloned from GitHub):

/usr/ictcore/sockets
/usr/ictcore/core/Socket

Ensure the following classes are use in AuthenticateApi:

use ICT\Core\Conf;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

===============================================================================

2.3 Create and Configure WebSocket Service

Create a systemd service file for the WebSocket server:

sudo nano /etc/systemd/system/socket_server.service

Add the following configuration:

[Unit]
Description=WebSocket Server
After=network.target

[Service]
ExecStart=/usr/ictcore/sockets/socket_servers.sh
Restart=always
User=ictcore
Group=ictcore
StandardOutput=append:/var/log/socket_server.log
StandardError=append:/var/log/socket_server_error.log

[Install]
WantedBy=multi-user.target

Save and exit the file.


Also Add Proxy on apache Server Configuration

Add this under 443: port for ssl 
# Proxy WebSocket Configuration
    ProxyPass /wss/ ws://127.0.0.1:8080/

Add this under 80: port for non-ssl
# Proxy WebSocket Configuration
    ProxyPass /wss/ ws://127.0.0.1:8090/
===============================================================================

2.4 Enable and Start the Service

Enable and start the WebSocket service:

systemctl enable socket_server
systemctl start socket_server

===============================================================================

2.5 Check Service Status

Verify the service status:

systemctl status socket_server

===============================================================================

3. Ports

The WebSocket server uses the following ports:

Port 8080: SSL port for domain-based communication (e.g., https://www.demo-fax.com).

Port 8090: Non-SSL port for IP-based communication (e.g., http://85.56.4.125).

===============================================================================

4. Directory and File Overview

4.1 /usr/ictcore/sockets

This directory contains the following files:

Readme: Information about ictcore_sockets.

socket-server.php: Handles non-SSL WebSocket connections on port 8090.

socket_server_ssl.php: Handles SSL WebSocket connections on port 8080.

socket_servers.sh: Script executed by the service to start the WebSocket server.

===============================================================================

4.2 /usr/ictcore/core/Socket

This directory contains the following files:

SocketMessage.php: Processes messages from the backend and forwards them to SocketHandler.php.

SocketHandler.php: Delivers real-time updates to connected clients.

===============================================================================

5. How Users Communicate with the WebSocket Server

Authentication:

Clients send a token to the WebSocket server for authentication.

The server validates the token using authentication APIs.

Real-Time Updates:

After successful authentication, the server sends real-time updates to the client through the WebSocket connection.

===============================================================================


