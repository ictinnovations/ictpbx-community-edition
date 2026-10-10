<?php
namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;

class FeatureCodeApi extends Api {

    protected $name = 'feature_codes';

    /**
     * Star codes that actually work on this system.
     *
     * This used to list FusionPBX's v_dialplans star codes, but FreeSWITCH runs only the
     * static XML ICTCore generates, so most of those (*98, *78, *72 ...) did nothing when
     * dialled. The list is now built from what ICTCore serves: *99 for the AI voice agent
     * when installed, *99<box> per voicemail box, and each call flow's toggle code.
     * Rows keep the old shape (dialplan_number, dialplan_name, ...) for the portal page.
     *
     * @url GET /feature_codes
     */
    public function list_view($query = array()) {
        $this->_authorize('user_admin');

        $db = \ICT\Core\FpbxDomain::fpbx_db();
        $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);

        $where = '';
        $args  = array();
        if (!$is_admin) {
            $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid((int)($this->oUser->tenant_id ?? 0));
            if (empty($domain_uuid)) {
                return array();
            }
            $where = ' AND domain_uuid = ?';
            $args  = array($domain_uuid);
        }

        $rows = array();
        if (is_file('/usr/ictcore/etc/freeswitch/dialplan/feature_codes/ictpbx_ai_99.xml')) {
            $rows[] = $this->row('ai_99', '*99', 'AI Voice Agent',
                'Talk to the AI assistant; it acts with the permissions of the user the extension is assigned to', 'true');
        }

        $stmt = $db->prepare("SELECT voicemail_uuid, voicemail_id, voicemail_enabled FROM v_voicemails
                              WHERE voicemail_id IS NOT NULL AND voicemail_id <> ''" . $where . "
                              ORDER BY voicemail_id");
        $stmt->execute($args);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $vm) {
            $rows[] = $this->row($vm['voicemail_uuid'], '*99' . $vm['voicemail_id'],
                'Check voicemail ' . $vm['voicemail_id'], 'Listen to messages in mailbox ' . $vm['voicemail_id'],
                $this->bool($vm['voicemail_enabled']));
        }

        $stmt = $db->prepare("SELECT call_flow_uuid, call_flow_name, call_flow_feature_code, call_flow_enabled
                              FROM v_call_flows
                              WHERE call_flow_feature_code IS NOT NULL AND call_flow_feature_code <> ''" . $where . "
                              ORDER BY call_flow_feature_code");
        $stmt->execute($args);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $cf) {
            $rows[] = $this->row($cf['call_flow_uuid'] . '_toggle', $cf['call_flow_feature_code'],
                'Toggle call flow: ' . $cf['call_flow_name'], 'Switch this call flow between Open (Day) and Closed (Night)',
                $this->bool($cf['call_flow_enabled']));
        }

        return $rows;
    }

    private function row($id, $code, $name, $description, $enabled) {
        return array(
            'dialplan_uuid'        => $id,
            'dialplan_number'      => $code,
            'dialplan_name'        => $name,
            'dialplan_description' => $description,
            'dialplan_enabled'     => $enabled,
        );
    }

    private function bool($v) {
        return ($v === false || $v === 'false' || $v === 'f' || $v === 0 || $v === '0') ? 'false' : 'true';
    }
}
