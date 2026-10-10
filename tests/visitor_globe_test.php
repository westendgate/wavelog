<?php
// Run with Wavelog's real Maidenhead converter, without booting the application.
define('BASEPATH', __DIR__);
require $argv[1].'/application/libraries/Qra.php';
require $argv[1].'/application/libraries/Visitor_globe.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$now = new DateTimeImmutable('2026-01-01 00:30:45', new DateTimeZone('UTC'));
check(Visitor_globe::window('24h', $now)['start'] === '2025-12-31 00:30:45', 'Rolling 24 hours crosses year');
check(Visitor_globe::window('month', $now)['start'] === '2026-01-01 00:00:00', 'Calendar month boundary');
check(Visitor_globe::window('year', $now)['start'] === '2026-01-01 00:00:00', 'Calendar year boundary');
check(Visitor_globe::window('all', $now)['start'] === NULL, 'All has no lower boundary');
try { Visitor_globe::window('invalid', $now); throw new RuntimeException('Invalid period accepted'); } catch (InvalidArgumentException $e) {}
$row = (object)array('COL_CALL'=>'TEST', 'COL_MY_VUCC_GRIDS'=>'', 'COL_MY_GRIDSQUARE'=>'JO31AA12BC', 'station_gridsquare'=>'JO31', 'COL_MY_LAT'=>NULL, 'COL_MY_LON'=>NULL, 'COL_VUCC_GRIDS'=>'', 'COL_GRIDSQUARE'=>'JJ00AA12BC', 'COL_LAT'=>NULL, 'COL_LON'=>NULL, 'qso_count'=>6001);
$repeat = clone $row; $repeat->qso_count=2;
$bad = clone $row; $bad->COL_GRIDSQUARE='BAD'; $bad->qso_count=3;
$explicit = clone $bad; $explicit->COL_LAT=0; $explicit->COL_LON=0; $explicit->qso_count=1;
$data = (new Visitor_globe())->build(array($row,$repeat,$bad,$explicit), new Qra(), function($r){return true;});
check($data['total']===6007 && $data['mapped']===6004 && $data['missing']===3, 'All counts and missing locations');
check(count($data['routes'])===2 && $data['routes'][0]['count']===6003, 'Repeated routes aggregate beyond 5000');
check($data['routes'][0]['endLat']===qra2latlong('JJ00AA12BC')[0], 'Ten-character locator precision');
check($data['routes'][1]['endLat']===0.0 && $data['routes'][1]['endLng']===0.0, 'Explicit zero coordinates are valid');
check(!isset($data['routes'][0]['COL_TIME_ON']), 'Only minimal public fields');
echo "PASS: periods, full locator precision, aggregation, missing locations and minimal payload\n";
