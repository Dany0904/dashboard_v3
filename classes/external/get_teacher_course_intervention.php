<?php

namespace local_dashboard_v3\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_teacher_course_intervention extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT),
            'limit' => new external_value(PARAM_INT, 'Top N cursos', VALUE_DEFAULT, 10)
        ]);
    }

    public static function execute($days, $limit = 10) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'days' => $days,
            'limit' => $limit
        ]);

        $days = in_array($params['days'], [7, 30, 90]) ? $params['days'] : 30;
        $limit = max(1, min(20, (int)$params['limit']));

        $now = time();
        $start = $now - ($days * 86400);

        // =========================
        // ROLES DOCENTE
        // =========================
        $roles = $DB->get_fieldset_sql("
            SELECT id
            FROM {role}
            WHERE shortname IN ('editingteacher', 'teacher')
        ");

        if (empty($roles)) {
            return ['data' => []];
        }

        $roles = array_map('intval', $roles);

        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r');

        // =========================
        // INTERVENCIÓN POR CURSO
        // =========================
        $records = $DB->get_records_sql("
            SELECT
                c.id,
                c.fullname as course,
                COUNT(l.id) as total
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            JOIN {course} c ON c.id = l.courseid
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            AND ra.roleid $insql
            GROUP BY c.id, c.fullname
            ORDER BY total DESC
        ", array_merge(['start' => $start], $inparams), 0, $limit);

        $result = [];

        foreach ($records as $r) {
            $result[] = [
                'label' => $r->course,
                'value' => (int)$r->total
            ];
        }

        return ['data' => $result];
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