<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

class get_user_segmentation extends external_api
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
        $start = $now - ($days * 86400);

        // =========================
        // FILTRO CURSO
        // =========================
        $coursefilter = '';
        $params = ['start' => $start];

        if (!empty($courseid)) {
            $coursefilter = "AND l.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // =========================
        // ACTIVOS
        // =========================
        $active = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            AND l.edulevel > 0
            $coursefilter
        ", $params);

        // =========================
        // NUEVOS
        // =========================
        $new = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {user}
            WHERE timecreated >= :start
            AND deleted = 0
        ", ['start' => $start]);

        // =========================
        // TOTAL
        // =========================
        $total = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {user}
            WHERE deleted = 0
            AND suspended = 0
        ");

        // =========================
        // DERIVADOS
        // =========================
        $recurrent = max($active - $new, 0);
        $inactive = max($total - $active, 0);

        return [
            'active' => $active,
            'new' => $new,
            'recurrent' => $recurrent,
            'inactive' => $inactive
        ];
    }

    public static function execute_returns()
    {
        return new external_single_structure([
            'active' => new external_value(PARAM_INT, 'Activos'),
            'new' => new external_value(PARAM_INT, 'Nuevos'),
            'recurrent' => new external_value(PARAM_INT, 'Recurrentes'),
            'inactive' => new external_value(PARAM_INT, 'Inactivos')
        ]);
    }
}