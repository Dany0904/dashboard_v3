<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class get_course_activity_chart extends external_api {

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
        $currentstart = $now - ($days * 86400);
        $previousstart = $now - ($days * 2 * 86400);
        $previousend = $currentstart;

        $coursefilter = '';
        $params_current = ['start' => $currentstart];
        $params_previous = [
            'start' => $previousstart,
            'end' => $previousend
        ];

        if (!empty($courseid)) {
            $coursefilter = "AND l.courseid = :courseid";
            $params_current['courseid'] = $courseid;
            $params_previous['courseid'] = $courseid;
        }

        // =========================
        // CURRENT
        // =========================
        $current = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            $coursefilter
            GROUP BY day
            ORDER BY day ASC
        ", $params_current);

        // =========================
        // PREVIOUS
        // =========================
        $previous = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(*) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.courseid <> 0
            $coursefilter
            GROUP BY day
            ORDER BY day ASC
        ", $params_previous);

        // =========================
        // FILL MISSING DAYS
        // =========================
        $current_filled = self::fill_missing_days_range(
            $current,
            $currentstart,
            $now
        );

        $previous_filled = self::fill_missing_days_range(
            $previous,
            $previousstart,
            $previousend
        );

        return [
            'current' => $current_filled,
            'previous' => $previous_filled
        ];
    }

    // =========================
    // HELPER: FILL DAYS
    // =========================
    private static function fill_missing_days_range($records, $start, $end)
    {
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