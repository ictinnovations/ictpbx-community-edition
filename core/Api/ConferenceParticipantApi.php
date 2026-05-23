<?php
namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;

class ConferenceParticipantApi extends Api {

    protected $name = 'conference_participants';

    /** @url GET /conference_participants */
    public function list_view($query = array()) {
        $this->_authorize_pbx('conferences');
        $conference_uuid = isset($_GET['conference_uuid']) ? trim($_GET['conference_uuid']) : '';
        if (empty($conference_uuid)) throw new CoreException(400, 'conference_uuid is required');

        $db = \ICT\Core\FpbxDomain::fpbx_db();

        $stmt = $db->prepare("SELECT conference_center_extension, domain_uuid FROM v_conference_centers WHERE conference_center_uuid = ?");
        $stmt->execute([$conference_uuid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) throw new CoreException(404, 'Conference not found');

        $room = $row['conference_center_extension'];
        $cmd  = "fs_cli -x 'conference " . escapeshellcmd($room) . " list' 2>/dev/null";
        $raw  = @shell_exec($cmd);
        if ($raw === null) $raw = '';

        $members = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            // ESL list line: id;uuid;caller_id_name;caller_id_number;codec;status;...
            if (preg_match('/^(\d+);([0-9a-f-]{36});([^;]*);([^;]*);[^;]*;([^;]*)/i', $line, $m)) {
                $members[] = [
                    'member_id'        => (int)$m[1],
                    'uuid'             => $m[2],
                    'caller_id_name'   => $m[3],
                    'caller_id_number' => $m[4],
                    'status'           => $m[5],
                ];
            }
        }

        return ['conference_center_extension' => $room, 'members' => $members];
    }

    /** @url POST /conference_participants */
    public function create($data = null) {
        $this->_authorize_pbx('conferences', true);
        if (empty($data['conference_uuid']) || empty($data['action'])) {
            throw new CoreException(400, 'conference_uuid and action are required');
        }

        $db = \ICT\Core\FpbxDomain::fpbx_db();

        $stmt = $db->prepare("SELECT conference_center_extension FROM v_conference_centers WHERE conference_center_uuid = ?");
        $stmt->execute([$data['conference_uuid']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) throw new CoreException(404, 'Conference not found');

        $allowed = ['mute', 'unmute', 'kick'];
        $action  = $data['action'];
        if (!in_array($action, $allowed, true)) throw new CoreException(400, 'Invalid action');

        $member = (int)($data['member_id'] ?? 0);
        if ($member < 1) throw new CoreException(400, 'member_id is required');

        $room = $row['conference_center_extension'];
        $cmd  = "fs_cli -x 'conference " . escapeshellcmd($room) . " $action $member' 2>/dev/null";
        @shell_exec($cmd);

        return ['status' => 'ok', 'action' => $action, 'member_id' => $member];
    }
}
