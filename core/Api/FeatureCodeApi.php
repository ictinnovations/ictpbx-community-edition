<?php
namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;

class FeatureCodeApi extends Api {

    protected $name = 'feature_codes';

    /** @url GET /feature_codes */
    public function list_view($query = array()) {
        $this->_authorize('user_admin');

        $db = \ICT\Core\FpbxDomain::fpbx_db();

        $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);

        if ($is_admin) {
            $stmt = $db->prepare(
                "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_description, dialplan_enabled
                 FROM v_dialplans
                 WHERE dialplan_number LIKE '*%'
                 ORDER BY dialplan_number ASC"
            );
            $stmt->execute();
        } else {
            $tenant_id = (int)($this->oUser->tenant_id ?? 0);
            $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid($tenant_id);
            $stmt = $db->prepare(
                "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_description, dialplan_enabled
                 FROM v_dialplans
                 WHERE dialplan_number LIKE '*%'
                   AND (domain_uuid = ? OR domain_uuid IS NULL)
                 ORDER BY dialplan_number ASC"
            );
            $stmt->execute([$domain_uuid]);
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }
}
