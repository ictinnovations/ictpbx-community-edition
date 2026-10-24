<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */


use ICT\Core\Api;
use ICT\Core\Announcement;
use ICT\Core\CoreException;
use ICT\Core\Corelog;

#[\AllowDynamicProperties]
class AnnouncementApi extends Api {

    /**
     * GET announcement
     * @url GET /announcement/$tenant_id
     */
    public function get_announcement($tenant_id){
     $announcement = new Announcement();
     return $announcement->get_announcement($tenant_id);
    }

    /**
     * create announcement
     * 
     * @url POST /announcement
     */
    public function add_announcement($data = array()){
        $this->_authorize('announcement');
        $announcement = new Announcement();
        $this->set($announcement, $data);
        return $announcement->save(); 
    }

}


 ?>