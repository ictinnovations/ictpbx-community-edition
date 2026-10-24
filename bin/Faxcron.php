#!/usr/bin/php -q

<?php

use ICT\Core\Transmission;
use ICT\Core\CoreException;
use ICT\Core\Result;
use ICT\Core\Corelog;
use ICT\Core\Gateway\Freeswitch;
use ICT\Core\DB;

require dirname(__DIR__).'/vendor/autoload.php'; // composer
require_once dirname(__FILE__).'/../core/core.php';

$application = $argv[1];
$application_id = $argv[2];
$freeswitch = $argv[3];
$serializedtransmission = $argv[4];
$application_data = $argv[5];
$serializedChannel = $argv[6];

$channelID = unserialize(base64_decode($serializedChannel));
$Transmission = unserialize(base64_decode($serializedtransmission));
$oApplication_data = unserialize(base64_decode($application_data));
Corelog::log("Fax processing | application : $application($application_id)", Corelog::DEBUG);

if ($freeswitch) {
    faxMonitor($freeswitch, $Transmission, $oApplication_data, $application, $channelID);
}

function faxMonitor($freeswitch, $Transmission, $oApplication_data, $application, $channelID) {
    global $pageNumber;
    $initiate = true;
    
    $file = fopen($freeswitch, 'r');
    fseek($file, 0, SEEK_END);
    while ($initiate) {
        clearstatcache();
        $currentPosition = ftell($file);
        fseek($file, $currentPosition);

        $oTransmission = new Transmission($Transmission->transmission_id);
        $oResult = Result::search(array('name' => $application, 'spool_id' => $oApplication_data->oTransmission->spool->spool_id));
        
        while (($line = fgets($file)) !== false) {
            if (strpos($line, $channelID) !== false && strpos($line, 'Page no') !== false) {
                preg_match('/=\s*(\S+)/', $line, $matches);
                $pageNumber = isset($matches[1]) ? $matches[1] : null; // Extracted page number
                if ($application === 'fax_receive') {
                    $pageNumber = shell_exec("identify $oApplication_data->fax_file | wc -l");
                }
                if( $pageNumber !== null) {
                    $pages = trim($pageNumber);
                    $query = "UPDATE transmission SET pages = '$pages' WHERE transmission_id = {$oTransmission->transmission_id}";
                    $result = DB::query('transmission', $query); // Assuming DB::query handles the database query
                    Corelog::log("Fax page: $pages Transmission: {$oTransmission->transmission_id} ", Corelog::DEBUG);
                }
            }
        }
        if ($oResult){
            if ($oResult[0]['data'] === 'success' || $oResult[0]['data'] === 'error') {
                Corelog::log("Fax monitoring process terminated", Corelog::DEBUG);
                $initiate = false;
            }
        }
        usleep(100000); // Sleep for 0.1 seconds
        }
        fclose($file);
    }
?>
