<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_user_activity_chart extends external_api
{
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT, 'Rango de días', VALUE_DEFAULT, 30),
            'courseid' => new external_value(PARAM_INT, 'Curso', VALUE_DEFAULT, 0)
        ]);
    }

    public static function execute($days = 30, $courseid = 0)
    {
        global $DB;

        self::validate_context(\context_system::instance());

        // Validación
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $now = time();

        $currentstart = $now - ($days * 86400);
        $currentend = $now;

        $previousstart = $now - ($days * 2 * 86400);
        $previousend = $currentstart;

        // Obtener datos
        $currentraw = self::get_activity_data($currentstart, $currentend, $courseid);
        $previousraw = self::get_activity_data($previousstart, $previousend, $courseid);

        // Normalizar
        $current = self::fill_missing_days_range($currentraw, $currentstart, $currentend);
        $previous = self::align_previous_period(
            $previousraw,
            $previousstart,
            $currentstart,
            $days
        );

        return [
            'current' => $current,
            'previous' => $previous
        ];
    }

    private static function get_activity_data($start, $end, $courseid)
    {
        global $DB;

        $params = [
            'start' => $start,
            'end' => $end
        ];

        $coursefilter = '';
        if (!empty($courseid)) {
            $coursefilter = "AND l.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        $sql = "
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(DISTINCT l.userid) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            $coursefilter
            GROUP BY day
            ORDER BY day ASC
        ";

        return $DB->get_records_sql($sql, $params);
    }

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

    private static function align_previous_period($records, $prevstart, $currentstart, $days)
    {
        $map = [];

        foreach ($records as $r) {
            $map[$r->day] = (int)$r->total;
        }

        $result = [];

        for ($i = 0; $i < $days; $i++) {

            $currentDay = date('Y-m-d', $currentstart + ($i * 86400));
            $previousDay = date('Y-m-d', $prevstart + ($i * 86400));

            $result[] = [
                'label' => date('d M', strtotime($currentDay)),
                'value' => $map[$previousDay] ?? 0
            ];
        }

        return $result;
    }

    public static function execute_returns()
    {
        return new external_single_structure([
            'current' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Usuarios')
                ])
            ),
            'previous' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Usuarios')
                ])
            )
        ]);
    }
}