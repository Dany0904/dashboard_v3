<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class get_teacher_activity_chart extends external_api {

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
        $prevstart = $now - ($days * 2 * 86400);
        $prevend = $start;

        // =========================
        // ROLES DOCENTE
        // =========================
        $teacherroleids = $DB->get_fieldset_sql("
            SELECT id FROM {role}
            WHERE shortname IN ('editingteacher', 'teacher')
        ");

        if (empty($teacherroleids)) {
            return [
                'current' => [],
                'previous' => []
            ];
        }

        list($rolein, $roleparams) = $DB->get_in_or_equal(
            $teacherroleids,
            SQL_PARAMS_NAMED,
            'r'
        );

        // =========================
        // CURRENT
        // =========================
        $current = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND ra.roleid $rolein
            GROUP BY day
            ORDER BY day ASC
        ", array_merge(['start' => $start], $roleparams));

        // =========================
        // PREVIOUS
        // =========================
        $previous = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated BETWEEN :start AND :end
            AND ra.roleid $rolein
            GROUP BY day
            ORDER BY day ASC
        ", array_merge([
            'start' => $prevstart,
            'end' => $prevend
        ], $roleparams));

        return [
            'current' => self::fill_days($current, $start, $now),
            'previous' => self::fill_days($previous, $prevstart, $prevend)
        ];
    }

    private static function fill_days($records, $start, $end) {

        $map = [];

        foreach ($records as $r) {
            $map[$r->day] = (int)$r->total;
        }

        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d', $start)),
            new \DateInterval('P1D'),
            (new \DateTime(date('Y-m-d', $end)))->modify('+1 day')
        );

        $result = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');

            $result[] = [
                'label' => $date->format('d M'),
                'value' => $map[$key] ?? 0
            ];
        }

        return $result;
    }

    public static function execute_returns() {
        return new external_single_structure([
            'current' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT),
                    'value' => new external_value(PARAM_INT)
                ])
            ),
            'previous' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT),
                    'value' => new external_value(PARAM_INT)
                ])
            )
        ]);
    }
}