<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Session-authenticated, read-only batch lookup for the user's active logbook.
class Hamdash_status extends CI_Controller {
    public function callsigns() {
        $this->output->set_content_type('application/json')->set_header('Cache-Control: no-store');
        if (!$this->user_model->authorize(2)) {
            $this->output->set_status_header(401)->set_output(json_encode(['error' => 'Anmeldung erforderlich']));
            return;
        }
        $calls = array_values(array_unique(explode(',', strtoupper(trim((string)$this->input->get('calls'))))));
        if (count($calls) > 200 || !$calls || array_filter($calls, function($call) { return !preg_match('/^[A-Z0-9\/]{3,20}$/', $call); })) {
            $this->output->set_status_header(400)->set_output(json_encode(['error' => 'Ungültige Rufzeichen']));
            return;
        }
        $this->load->model('logbooks_model');
        $id = $this->logbooks_model->find_active_station_logbook_from_userid($this->session->userdata('user_id'));
        if (!$id || !$this->logbooks_model->check_logbook_is_accessible($id)) {
            $this->output->set_status_header(403)->set_output(json_encode(['error' => 'Kein zugängliches Logbuch']));
            return;
        }
        $locations = $this->logbooks_model->list_logbook_relationships($id);
        $table = $this->config->item('table_name');
        $sources = ['LoTW'=>'COL_LOTW_QSL_RCVD', 'eQSL'=>'COL_EQSL_QSL_RCVD', 'QRZ.com'=>'COL_QRZCOM_QSO_DOWNLOAD_STATUS', 'Club Log'=>'COL_CLUBLOG_QSO_DOWNLOAD_STATUS', 'DCL'=>'COL_DCL_QSL_RCVD'];
        $select = "COL_CALL AS callsign, COL_BAND AS band, COALESCE(NULLIF(COL_SUBMODE, ''), COL_MODE) AS mode, MAX(COL_TIME_ON) AS last_qso";
        $available = [];
        foreach ($sources as $label => $field) {
            if ($this->db->field_exists($field, $table)) {
                $alias = 'source_' . count($available);
                $select .= ", MAX(CASE WHEN `$field` = 'Y' THEN 1 ELSE 0 END) AS $alias";
                $available[$alias] = $label;
            }
        }
        $rows = $this->db->select($select, false)->where_in('station_id', $locations)->where_in('COL_CALL', $calls)
            ->group_by(['COL_CALL', 'COL_BAND', "COALESCE(NULLIF(COL_SUBMODE, ''), COL_MODE)"], false)->get($table)->result_array();
        $result = array_fill_keys($calls, []);
        foreach ($rows as $row) {
            $confirmed = [];
            foreach ($available as $alias => $label) { if ($row[$alias]) $confirmed[] = $label; }
            $result[strtoupper($row['callsign'])][] = ['band'=>strtolower($row['band']), 'mode'=>strtoupper($row['mode']), 'last_qso'=>$row['last_qso'], 'sources'=>$confirmed];
        }
        $this->output->set_output(json_encode(['logbook_id'=>(int)$id, 'calls'=>$result]));
    }
}
