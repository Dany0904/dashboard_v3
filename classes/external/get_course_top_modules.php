<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class get_course_top_modules extends external_api {

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
        // QUERY COMPONENTES
        // =========================
        $records = $DB->get_records_sql("
            SELECT 
                l.component,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            AND l.component LIKE 'mod_%'
            $coursefilter
            GROUP BY l.component
            ORDER BY total DESC
        ", $params_sql);

        // =========================
        // MAPEO NOMBRE AMIGABLE
        // =========================
        $mapnames = [
            'mod_forum' => 'Foros',
            'mod_quiz' => 'Quizzes',
            'mod_assign' => 'Tareas',
            'mod_resource' => 'Recursos',
            'mod_page' => 'Páginas',
            'mod_url' => 'URLs',
            'mod_scorm' => 'SCORM'
        ];

        $result = [];

        foreach ($records as $r) {
            $label = $mapnames[$r->component] ?? $r->component;

            $result[] = [
                'label' => $label,
                'value' => (int)$r->total
            ];
        }

        return [
            'data' => array_values($result)
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