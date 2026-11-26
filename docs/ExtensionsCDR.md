
Note: follow this guide after installing the ictfax database properly.
======================================================================================

==========================
To enable CDR functionality for freeswitch sip users install freeswitch-event-cdr-odbc.

yum install freeswitch-event-cdr-odbc


Create odbc.ini link to enable open database conectivity for mysql database.

ln -s /usr/ictcore/etc/odbc.ini /etc/odbc.ini


Create link odbc_cdr.conf.xml to load freeswitch-event-cdr-odbc configs.

ln -s /usr/ictcore/etc/freeswitch/mod/odbc_cdr.conf.xml /etc/freeswitch/autoload_config/odbc_cdr.conf.xml


Now uncomment this mdoule in module.conf.xml file. if not  already added please add.


In freeswitch CLI run this command. If you not seeing any red lines that means module loaded successfully.

load mod_odbc_cdr
