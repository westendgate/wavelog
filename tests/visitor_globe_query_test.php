<?php
class CI_Model {}
require $argv[1].'/application/models/Visitor_model.php';
class QueryRecorder {
    public $calls = array();
    function __call($method, $args) { $this->calls[] = array($method, $args); return $this; }
    function result() { return array(); }
}
class GlobeConfig { function item($key) { return 'qsos'; } }
class TestVisitorModel extends Visitor_model { public $db; public $config; }
$model = new TestVisitorModel();
$model->db = new QueryRecorder(); $model->config = new GlobeConfig();
$model->get_globe_qsos(array(7,9), array('start'=>'2026-10-09 17:15:00','end'=>'2026-10-10 17:15:00'));
$calls = $model->db->calls;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
check(in_array(array('where_in', array('qsos.station_id', array(7,9))), $calls, true), 'Public station scope must be applied');
check(in_array(array('where', array('qsos.COL_TIME_ON >=', '2026-10-09 17:15:00')), $calls, true), 'Exact rolling time must not truncate to date');
check(in_array(array('where', array('qsos.COL_TIME_ON <=', '2026-10-10 17:15:00')), $calls, true), 'Future QSOs excluded');
check(!in_array('limit', array_column($calls,0), true), 'No total-QSO cap');
$model->db = new QueryRecorder();
$model->get_globe_qsos(array(7), array('start'=>NULL,'end'=>'2026-10-10 17:15:00'));
$wheres = array_filter($model->db->calls, function($call){return $call[0]==='where';});
check(count($wheres)===1, 'All has only an upper time bound');
echo "PASS: published station scope, precise time filters, full-logbook query\n";
