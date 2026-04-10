<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_dashboard_v3_tables extends external_api
{
    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT, 'Rango de días', VALUE_DEFAULT, 30)
        ]);
    }

    public static function execute($days = 30)
    {
        global $DB;

        self::validate_context(\context_system::instance());

        // Validación
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $start = time() - ($days * 86400);

        // =========================
        // Cursos demandados
        // =========================
        $enrollments = $DB->get_records_sql("
            SELECT c.fullname AS name,
                   COUNT(ue.id) AS total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.timecreated >= :start
            AND c.id <> :siteid
            GROUP BY c.id, c.fullname
            ORDER BY total DESC
            LIMIT 10
        ", [
            'start' => $start,
            'siteid' => SITEID
        ]);

        $enrollmentsdata = array_values($enrollments);

        // =========================
        // Promedios
        // =========================
        $promedios = $DB->get_records_sql("
            SELECT c.fullname AS name,
                   AVG(gg.finalgrade) AS average
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            JOIN {course} c ON c.id = gi.courseid
            WHERE c.id <> :siteid
            AND gg.finalgrade IS NOT NULL
            GROUP BY c.id, c.fullname
            HAVING COUNT(gg.id) > 5
            ORDER BY average DESC
            LIMIT 10
        ", ['siteid' => SITEID]);

        $averages = [];
        foreach ($promedios as $row) {
            $averages[] = [
                'name' => $row->name,
                'average' => round((float)$row->average, 2)
            ];
        }

        // =========================
        // Actividad por curso
        // =========================
        $activity = $DB->get_records_sql("
            SELECT c.fullname AS name,
                   COUNT(DISTINCT l.userid) AS total
            FROM {logstore_standard_log} l
            JOIN {course} c ON c.id = l.courseid
            WHERE l.timecreated >= :start
            AND l.courseid <> :siteid
            AND l.userid <> 1
            GROUP BY c.id, c.fullname
            ORDER BY total DESC
            LIMIT 10
        ", [
            'start' => $start,
            'siteid' => SITEID
        ]);

        $activitycourses = array_values($activity);

        // =========================
        // Progreso (placeholder)
        // =========================
        $progress = [];

        // =========================
        // Finalizado vs No finalizado (placeholder)
        // =========================
        $vs = [];

        return [
            'enrollments' => $enrollmentsdata,
            'averages' => $averages,
            'activitycourses' => $activitycourses,
            'progress' => $progress,
            'vs' => $vs
        ];
    }

    public static function execute_returns()
    {
        return new external_single_structure([

            'enrollments' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Curso'),
                    'total' => new external_value(PARAM_INT, 'Inscripciones')
                ])
            ),

            'averages' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Curso'),
                    'average' => new external_value(PARAM_FLOAT, 'Promedio')
                ])
            ),

            'activitycourses' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Curso'),
                    'total' => new external_value(PARAM_INT, 'Usuarios activos')
                ])
            ),

            'progress' => new external_multiple_structure(
                new external_single_structure([
                    'fullname' => new external_value(PARAM_TEXT, 'Curso'),
                    'progress' => new external_value(PARAM_FLOAT, 'Progreso')
                ])
            ),

            'vs' => new external_multiple_structure(
                new external_single_structure([
                    'fullname' => new external_value(PARAM_TEXT, 'Curso'),
                    'completed' => new external_value(PARAM_INT, 'Completados'),
                    'notcompleted' => new external_value(PARAM_INT, 'No completados')
                ])
            )

        ]);
    }
}