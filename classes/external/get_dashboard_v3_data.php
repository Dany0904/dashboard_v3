<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_dashboard_v3_data extends external_api
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

        // Validar días
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $now = time();
        $start = $now - ($days * 86400);

        // =========================
        // KPIs básicos (no dependen de días)
        // =========================
        $users = $DB->count_records('user');
        $courses = $DB->count_records('course');
        $categories = $DB->count_records('course_categories');

        // Usuarios en línea
        $onlineusers = $DB->count_records_select(
            'user_lastaccess',
            'timeaccess > ?',
            [$now - 300]
        );

        // =========================
        // Usuarios por periodo
        // =========================
        $sql = "
            SELECT DATE(FROM_UNIXTIME(u.timecreated)) as day,
                   COUNT(1) as total
            FROM {user} u
            WHERE u.timecreated >= :start
            AND u.deleted = 0
            GROUP BY day
            ORDER BY day ASC
        ";

        $usersbyperiod = $DB->get_records_sql($sql, ['start' => $start]);

        $chartdata = [];
        foreach ($usersbyperiod as $row) {
            $chartdata[] = [
                'year' => $row->day,
                'total' => (int)$row->total
            ];
        }

        // =========================
        // Cursos por periodo
        // =========================
        $sql = "
            SELECT DATE(FROM_UNIXTIME(c.timecreated)) as day,
                   COUNT(1) as total
            FROM {course} c
            WHERE c.timecreated >= :start
            AND c.id <> :siteid
            GROUP BY day
            ORDER BY day ASC
        ";

        $coursesbyperiod = $DB->get_records_sql($sql, [
            'start' => $start,
            'siteid' => SITEID
        ]);

        $courseschart = [];
        foreach ($coursesbyperiod as $row) {
            $courseschart[] = [
                'year' => $row->day,
                'total' => (int)$row->total
            ];
        }

        // =========================
        // Cursos por categoría
        // =========================
        $sql = "
            SELECT c.id,
                   c.name,
                   COUNT(co.id) AS total_courses
            FROM {course_categories} c
            LEFT JOIN {course} co 
                ON co.category = c.id
                AND co.id <> :siteid
            GROUP BY c.id, c.name
            ORDER BY total_courses DESC
            LIMIT 10
        ";

        $categoriesdata = $DB->get_records_sql($sql, ['siteid' => SITEID]);

        $categorieschart = [];
        foreach ($categoriesdata as $cat) {
            $categorieschart[] = [
                'id' => (int)$cat->id,
                'name' => (string)$cat->name,
                'total' => (int)$cat->total_courses
            ];
        }

        // =========================
        // Cursos demandados (por rango de días)
        // =========================
        $sql = "
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
        ";

        $enrollmentsdata = array_values($DB->get_records_sql($sql, [
            'start' => $start,
            'siteid' => SITEID
        ]));

        // =========================
        // Promedios
        // =========================
        $sql = "
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
        ";

        $promedios = $DB->get_records_sql($sql, ['siteid' => SITEID]);

        $promediosdata = [];
        foreach ($promedios as $row) {
            $promediosdata[] = [
                'name' => $row->name,
                'average' => round((float)$row->average, 2)
            ];
        }

        // =========================
        // Actividad por curso
        // =========================
        $sql = "
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
        ";

        $activitydata = array_values($DB->get_records_sql($sql, [
            'start' => $start,
            'siteid' => SITEID
        ]));

        // =========================
        // Progreso
        // =========================
        $progressdata = []; // lo dejamos igual por ahora (no depende directo de días)

        // =========================
        // Finalizado vs No Finalizado
        // =========================
        $vsdata = []; // igual dejamos intacto para no romper lógica

        return [
            'users' => $users,
            'courses' => $courses,
            'categories' => $categories,
            'onlineusers' => $onlineusers,
            'usersbyyear' => $chartdata,
            'coursesbyyear' => $courseschart,
            'categoriescount' => $categories,
            'coursesbycategory' => $categorieschart,
            'enrollments' => $enrollmentsdata,
            'averages' => $promediosdata,
            'activitycourses' => $activitydata,
            'progress' => $progressdata,
            'vs' => $vsdata
        ];
    }

    public static function execute_returns()
    {
        return new external_single_structure([
            'users' => new external_value(PARAM_INT, 'Total users'),
            'courses' => new external_value(PARAM_INT, 'Total courses'),
            'categories' => new external_value(PARAM_INT, 'Total categories'),
            'onlineusers' => new external_value(PARAM_INT, 'Users online'),

            'usersbyyear' => new external_multiple_structure(
                new external_single_structure([
                    'year' => new external_value(PARAM_TEXT, 'Fecha'),
                    'total' => new external_value(PARAM_INT, 'Total')
                ])
            ),

            'coursesbyyear' => new external_multiple_structure(
                new external_single_structure([
                    'year' => new external_value(PARAM_TEXT, 'Fecha'),
                    'total' => new external_value(PARAM_INT, 'Total')
                ])
            ),

            'categoriescount' => new external_value(PARAM_INT, 'Total categories'),

            'coursesbycategory' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Category ID'),
                    'name' => new external_value(PARAM_TEXT, 'Category name'),
                    'total' => new external_value(PARAM_INT, 'Total courses')
                ])
            ),

            'enrollments' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Course name'),
                    'total' => new external_value(PARAM_INT, 'Enrollments')
                ])
            ),

            'averages' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Course name'),
                    'average' => new external_value(PARAM_FLOAT, 'Average')
                ])
            ),

            'activitycourses' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Course name'),
                    'total' => new external_value(PARAM_INT, 'Active users')
                ])
            ),

            'progress' => new external_multiple_structure(
                new external_single_structure([
                    'fullname' => new external_value(PARAM_TEXT, 'Course name'),
                    'progress' => new external_value(PARAM_FLOAT, 'Progress')
                ])
            ),

            'vs' => new external_multiple_structure(
                new external_single_structure([
                    'fullname' => new external_value(PARAM_TEXT, 'Course name'),
                    'completed' => new external_value(PARAM_INT, 'Completed'),
                    'notcompleted' => new external_value(PARAM_INT, 'Not completed')
                ])
            )
        ]);
    }
}