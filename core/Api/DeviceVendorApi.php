<?php

namespace ICT\Core\Api;

use ICT\Core\Api;

#[\AllowDynamicProperties]
class DeviceVendorApi extends Api
{
  const TEMPLATE_DIR = '/var/www/fusionpbx/resources/templates/provision';

  /**
   * @url GET /device_vendors
   */
  public function list_view()
  {
    $this->_authorize('user_admin');
    $result = [];
    if (!is_dir(self::TEMPLATE_DIR)) return $result;

    $vendors = array_filter(scandir(self::TEMPLATE_DIR), function($entry) {
      return $entry !== '.' && $entry !== '..' &&
             is_dir(self::TEMPLATE_DIR . '/' . $entry);
    });

    foreach (array_values($vendors) as $vendor) {
      $vendor_path = self::TEMPLATE_DIR . '/' . $vendor;
      $models = array_filter(scandir($vendor_path), function($entry) use ($vendor_path) {
        return $entry !== '.' && $entry !== '..' &&
               is_dir($vendor_path . '/' . $entry);
      });
      $result[] = [
        'name'   => $vendor,
        'models' => array_values($models),
      ];
    }
    return $result;
  }
}
