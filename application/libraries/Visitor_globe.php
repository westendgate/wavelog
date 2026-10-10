<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/** Public globe data preparation; Maidenhead conversion stays in Wavelog's Qra. */
class Visitor_globe {
    public static function window($period, $now = NULL) {
        $now = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'));
        switch ($period) {
            case '24h': $start = $now->modify('-24 hours'); break;
            case 'month': $start = $now->modify('first day of this month')->setTime(0, 0); break;
            case 'year': $start = $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0); break;
            case 'all': $start = NULL; break;
            default: throw new InvalidArgumentException('Unknown period');
        }
        return array('start' => $start ? $start->format('Y-m-d H:i:s') : NULL, 'end' => $now->format('Y-m-d H:i:s'));
    }

    private function coordinates($grid, $lat, $lng, $qra) {
        $grid = preg_replace('/\s+/', '', strtoupper($grid ?? ''));
        // Validate first: Qra returns (0,0) for some invalid lengths.
        $valid = true;
        $grids = explode(',', $grid);
        foreach ($grids as $g) {
            if (!preg_match('/^[A-R]{2}(?:[0-9]{2}(?:[A-X]{2}(?:[0-9]{2}(?:[A-X]{2})?)?)?)?$/', $g)) $valid = false;
        }
        if ($valid && (count($grids) === 1 || count($grids) === 2 || count($grids) === 4)) {
            $coords = $qra->qra2latlong($grid);
            if (is_array($coords) && count($coords) === 2) return array((float)$coords[0], (float)$coords[1]);
        }
        if (is_numeric($lat) && is_numeric($lng) && abs((float)$lat) <= 90 && abs((float)$lng) <= 180) {
            return array((float)$lat, (float)$lng);
        }
        return NULL;
    }

    public function build($rows, $qra, $confirmed) {
        $routes = array(); $total = 0; $mapped = 0;
        foreach ($rows as $row) {
            $count = (int)$row->qso_count;
            $total += $count;
            $home = $this->coordinates($row->COL_MY_VUCC_GRIDS ?: ($row->COL_MY_GRIDSQUARE ?: $row->station_gridsquare), $row->COL_MY_LAT, $row->COL_MY_LON, $qra);
            $target = $this->coordinates($row->COL_VUCC_GRIDS ?: $row->COL_GRIDSQUARE, $row->COL_LAT, $row->COL_LON, $qra);
            if ($home === NULL || $target === NULL) continue;
            $isConfirmed = (bool)$confirmed($row);
            $call = (string)$row->COL_CALL;
            $key = json_encode(array($home, $target, $call, $isConfirmed));
            if (!isset($routes[$key])) {
                $routes[$key] = array('startLat' => $home[0], 'startLng' => $home[1], 'endLat' => $target[0], 'endLng' => $target[1], 'call' => $call, 'confirmed' => $isConfirmed, 'count' => 0);
            }
            $routes[$key]['count'] += $count;
            $mapped += $count;
        }
        return array('total' => $total, 'mapped' => $mapped, 'missing' => $total - $mapped, 'routes' => array_values($routes));
    }
}
