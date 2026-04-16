<?php

namespace local_dashboard_v3\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_system_performance_chart extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT)
        ]);
    }

    public static function execute($days) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'days' => $days
        ]);

        $days = in_array($params['days'], [7, 30, 90]) ? $params['days'] : 30;

        $now = time();
        $start = $now - ($days * 86400);

        // =========================
        // REQUESTS (LOGS)
        // =========================
        $records = $DB->get_records_sql("
            SELECT
                DATE(FROM_UNIXTIME(timecreated)) as day,
                COUNT(*) as total_requests
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            GROUP BY DATE(FROM_UNIXTIME(timecreated))
            ORDER BY day ASC
        ", ['start' => $start]);

        // =========================
        // ERRORS (REAL APPROACH)
        // =========================
        $errors = $DB->get_records_sql("
            SELECT
                DATE(FROM_UNIXTIME(timecreated)) as day,
                COUNT(*) as total_errors
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            AND action = 'error'
            GROUP BY DATE(FROM_UNIXTIME(timecreated))
            ORDER BY day ASC
        ", ['start' => $start]);

        // =========================
        // MAPS
        // =========================
        $map_requests = [];
        $map_errors = [];

        foreach ($records as $r) {
            $map_requests[$r->day] = (int)$r->total_requests;
        }

        foreach ($errors as $e) {
            $map_errors[$e->day] = (int)$e->total_errors;
        }

        // =========================
        // RESPONSE TIME (SIMULADO CONTROLADO)
        // =========================
        // Moodle no guarda ms reales por defecto
        // se estima con carga de logs (proxy razonable)
        $response_map = [];

        foreach ($map_requests as $day => $req) {
            $response_map[$day] = round(80 + ($req * 0.02), 2); // baseline + carga
        }

        // =========================
        // FILL DAYS
        // =========================
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d', $start)),
            new \DateInterval('P1D'),
            (new \DateTime(date('Y-m-d', $now)))->modify('+1 day')
        );

        $result = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');

            $result[] = [
                'label' => $date->format('d M'),
                'requests' => $map_requests[$key] ?? 0,
                'response' => $response_map[$key] ?? 0,
                'errors' => $map_errors[$key] ?? 0
            ];
        }

        return ['data' => $result];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT),
                    'requests' => new external_value(PARAM_INT),
                    'response' => new external_value(PARAM_FLOAT),
                    'errors' => new external_value(PARAM_INT)
                ])
            )
        ]);
    }
}