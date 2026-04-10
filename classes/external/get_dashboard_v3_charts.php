<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_dashboard_v3_charts extends external_api
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
        // Usuarios por periodo
        // =========================
        $usersbyperiod = $DB->get_records_sql("
            SELECT DATE(FROM_UNIXTIME(u.timecreated)) as day,
                   COUNT(1) as total
            FROM {user} u
            WHERE u.timecreated >= :start
            AND u.deleted = 0
            GROUP BY day
            ORDER BY day ASC
        ", ['start' => $start]);

        $users = [];
        foreach ($usersbyperiod as $row) {
            $users[] = [
                'label' => $row->day,
                'value' => (int)$row->total
            ];
        }

        // =========================
        // Cursos por periodo
        // =========================
        $coursesbyperiod = $DB->get_records_sql("
            SELECT DATE(FROM_UNIXTIME(c.timecreated)) as day,
                   COUNT(1) as total
            FROM {course} c
            WHERE c.timecreated >= :start
            AND c.id <> :siteid
            GROUP BY day
            ORDER BY day ASC
        ", [
            'start' => $start,
            'siteid' => SITEID
        ]);

        $courses = [];
        foreach ($coursesbyperiod as $row) {
            $courses[] = [
                'label' => $row->day,
                'value' => (int)$row->total
            ];
        }

        // =========================
        // Cursos por categoría
        // =========================
        $categoriesdata = $DB->get_records_sql("
            SELECT c.name,
                   COUNT(co.id) AS total
            FROM {course_categories} c
            LEFT JOIN {course} co 
                ON co.category = c.id
                AND co.id <> :siteid
            GROUP BY c.id, c.name
            ORDER BY total DESC
            LIMIT 10
        ", ['siteid' => SITEID]);

        $categories = [];
        foreach ($categoriesdata as $cat) {
            $categories[] = [
                'label' => $cat->name,
                'value' => (int)$cat->total
            ];
        }

        return [
            'users' => $users,
            'courses' => $courses,
            'categories' => $categories
        ];
    }

    public static function execute_returns()
    {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Total')
                ])
            ),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Total')
                ])
            ),
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Categoría'),
                    'value' => new external_value(PARAM_INT, 'Total cursos')
                ])
            )
        ]);
    }
}