<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class get_course_activity_weekday extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT),
            'courseid' => new external_value(PARAM_INT)
        ]);
    }

    public static function execute($days, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'days' => $days,
            'courseid' => $courseid
        ]);

        $days = in_array($params['days'], [7, 30, 90]) ? $params['days'] : 30;
        $courseid = $params['courseid'];

        $now = time();
        $start = $now - ($days * 86400);

        $coursefilter = '';
        $params_sql = ['start' => $start];

        if (!empty($courseid)) {
            $coursefilter = "AND l.courseid = :courseid";
            $params_sql['courseid'] = $courseid;
        }

        // =========================
        // QUERY (0=Sunday, 6=Saturday)
        // =========================
        $records = $DB->get_records_sql("
            SELECT 
                DAYOFWEEK(FROM_UNIXTIME(l.timecreated)) as weekday,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            $coursefilter
            GROUP BY weekday
        ", $params_sql);

        // =========================
        // MAPA COMPLETO
        // =========================
        $map = [];

        foreach ($records as $r) {
            $map[(int)$r->weekday] = (int)$r->total;
        }

        // =========================
        // ORDEN LUNES → DOMINGO
        // MySQL: 1=Sunday ... 7=Saturday
        // =========================
        $days_order = [
            2 => 'Lun',
            3 => 'Mar',
            4 => 'Mié',
            5 => 'Jue',
            6 => 'Vie',
            7 => 'Sáb',
            1 => 'Dom'
        ];

        $result = [];

        foreach ($days_order as $key => $label) {
            $result[] = [
                'label' => $label,
                'value' => $map[$key] ?? 0
            ];
        }

        return [
            'data' => $result
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT),
                    'value' => new external_value(PARAM_INT)
                ])
            )
        ]);
    }
}