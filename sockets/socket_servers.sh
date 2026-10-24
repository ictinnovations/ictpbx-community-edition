#!/bin/bash

# Start the first PHP script
/usr/bin/php /usr/ictcore/sockets/socket_server.php &

# Start the second PHP script
/usr/bin/php /usr/ictcore/sockets/socket_server_ssl.php &

# Wait for both processes to finish
wait

