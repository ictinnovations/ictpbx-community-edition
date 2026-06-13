<?php
namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\DB;
use ICT\Core\Route;
use ICT\Core\CoreException;

#[\AllowDynamicProperties]
class RouteApi extends Api
{

  /**
   * Create a new Route
   *
   * @url POST /routes
   */
  public function create($data = array())
  {
    $this->_authorize('route_create');

    $oRoute = new Route();
    $this->set($oRoute, $data);

    if ($oRoute->save()) {
      return $oRoute->route_id;
    } else {
      throw new CoreException(417, 'Route creation failed');
    }
  }

  /**
   * Create a new Routes in bulk
   *
   * @url POST /routes/bulk
   */
  public function create_bulk($data = array())
  {
    $this->_authorize('route_create');
    
    $i = 0;
    foreach ($data as $destination) {
      $oRoute = new Route();
      $this->set($oRoute, $destination);

      if ($oRoute->save()) {
        $i++;
      } else {
        throw new CoreException(417, 'Route creation failed');
      }
    }
    return $i." Routes added.";
  }

  /**
   * List all available Routes
   *
   * @url GET /routes
   */
  public function list_view($query = array())
  {
    $this->_authorize('route_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Route::search($filter);
  }

  /**
   * Delete a new Route
   *
   * @url DELETE /routes/$route_id
   */
  public function remove($route_id)
  {
    $this->_authorize('route_delete');

    $oRoute = new Route($route_id);

    $result = $oRoute->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Route delete failed');
    }
  }
  
  /**
   * Provide Route Sample
   *
   * @url GET /routes/sample/csv
   */
  public function sample_csv()
  {
    global $path_data;
    $sample_route = $path_data . DIRECTORY_SEPARATOR . 'route_sample.csv';
    if (file_exists($sample_route)) {
      return new SplFileInfo($sample_route);
    } else {
      throw new CoreException(404, "File not found");
    }
  }
  
  /**
   * Import Routes in bulk
   *
   * @url POST /routes/$service_flag/$provider_id/csv
   */
  public function import_csv($service_flag, $provider_id, $data = array(), $mime = 'text/csv')
  {
    $total_rows = 0;
     if (!empty($data)) {
       $file_name = tempnam('/tmp', 'route') . ".csv";
       file_put_contents($file_name, $data);
       $csv_columns = array(
         'destination_id'    => '',
         'name'              => '', 
       );
       
       exec("dos2unix '$file_name'");
       
       $handle  = fopen($file_name, "r");
    // get a sample row
    if (($data = fgetcsv($handle, 500, ",")) !== false) {
      // remove extra columns from csv_columns
      array_splice($csv_columns, count($data));
    }
    fclose($handle);

    $sql_columns = implode(', ', array_keys($csv_columns));

    $query = "LOAD DATA LOW_PRIORITY LOCAL INFILE '$file_name'
                IGNORE INTO TABLE route
                FIELDS
                    TERMINATED BY ','
                    OPTIONALLY ENCLOSED BY '\"'
                LINES
                    TERMINATED BY '\\n'
                ($sql_columns)
                SET service_flag = $service_flag, provider_id = $provider_id
            ";
    $result     = mysqli_query(DB::$link, $query);
    $total_rows = mysqli_affected_rows(DB::$link);

    return $total_rows;
     }
     else {
       throw new CoreException(411, 'Route CSV upload failed, no file uploaded');
     }
  }
  
  /**
   * Export Routes
   *
   * @url GET /routes/csv
   * 
   */
  public function export_csv($query = array())
  {
    $listRoute = $this->list_view($query);
    if ($listRoute) {
      $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_route'.'.csv';
      $handle = fopen($file_path, 'w');
      if (!$handle) {
        throw new CoreException(500, "Unable to open file");
      }
      foreach($listRoute as $aValue) {
        $route_row = '"'.$aValue['destination_id'].'","'.$aValue['name'].'","'.$aValue['service_flag'].'",'.
                       '"'.$aValue['provider_id'].'"'."\n";
        fwrite($handle, $route_row);
      }
      fclose($handle);
      return new SplFileInfo($file_path);
    } else {
      throw new CoreException(404, "File not found");
    }
  }
}
