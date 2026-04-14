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

        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        // NUEVO: centralizar fechas
        list($startDate, $endDate) = self::get_date_range($days);
        $start = $startDate->getTimestamp();

        // =========================
        // Actividad de usuarios (OPTIMIZADA)
        // =========================
        $activityraw = $DB->get_records_sql("
            SELECT DATE(FROM_UNIXTIME(l.timecreated)) as day,
                   COUNT(DISTINCT l.userid) as total
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            AND l.target = 'course_module'
            GROUP BY day
            ORDER BY day ASC
        ", ['start' => $start]);

        $activity = self::fill_missing_days($activityraw, $startDate, $endDate);

        // =========================
        // Abandono de cursos
        // =========================
        $abandonraw = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(ue.timecreated)) as day,
                COUNT(DISTINCT ue.userid) as enrolled,
                COUNT(DISTINCT cc.userid) as completed
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            LEFT JOIN {course_completions} cc 
                ON cc.userid = ue.userid 
                AND cc.course = e.courseid
            WHERE ue.timecreated >= :start
            GROUP BY day
            ORDER BY day ASC
        ", ['start' => $start]);

        $abandonmap = [];

        foreach ($abandonraw as $row) {
            $enrolled = (int)$row->enrolled;
            $completed = (int)$row->completed;

            $drop = $enrolled - $completed;

            $percentage = $enrolled > 0
                ? round(($drop / $enrolled) * 100, 2)
                : 0;

            $abandonmap[$row->day] = $percentage;
        }

        $abandon = self::fill_missing_days_custom($abandonmap, $startDate, $endDate);

        // =========================
        // Cursos más populares (OPTIMIZADO)
        // AND l.target = 'course_module'
        // =========================
        $popularcourses = $DB->get_records_sql("
            SELECT 
                c.fullname AS name,
                COUNT(DISTINCT l.userid) AS total
            FROM {logstore_standard_log} l
            JOIN {course} c ON c.id = l.courseid
            WHERE l.timecreated >= :start
            AND l.courseid <> :siteid
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
           
            GROUP BY c.id, c.fullname
            ORDER BY total DESC
            LIMIT 10
        ", [
            'start' => $start,
            'siteid' => SITEID
        ]);

        $popularcoursesdata = [];

        foreach ($popularcourses as $course) {
            $popularcoursesdata[] = [
                'label' => $course->name,
                'value' => (int)$course->total
            ];
        }

        // =========================
        // Actividad de docentes (CORREGIDO)
        // =========================
        $teachersraw = $DB->get_records_sql("
            SELECT 
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(DISTINCT l.userid) as total
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            JOIN {context} ctx ON ctx.id = ra.contextid
            JOIN {course} c ON c.id = ctx.instanceid
            JOIN {role} r ON r.id = ra.roleid
            WHERE l.timecreated >= :start
            AND l.courseid = c.id
            AND ctx.contextlevel = 50
            AND r.shortname IN ('editingteacher', 'teacher')
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            AND l.target = 'course_module'
            GROUP BY day
            ORDER BY day ASC
        ", ['start' => $start]);

        $teachers = self::fill_missing_days($teachersraw, $startDate, $endDate);

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

        return [
            'activity' => $activity,
            'abandon' => $abandon,
            'popularcourses' => $popularcoursesdata,
            'teachers' => $teachers,
            'enrollments' => $enrollmentsdata,
            'averages' => $averages
        ];
    }

    // =========================
    // NUEVO: Helper de fechas
    // =========================
    private static function get_date_range($days)
    {
        $end = new \DateTime();
        $start = (clone $end)->modify("-$days days");

        return [$start, $end];
    }

    // =========================
    // Helper días faltantes
    // =========================
    private static function fill_missing_days($records, $startDate, $endDate)
    {
        $map = [];

        foreach ($records as $r) {
            $map[$r->day] = (int)$r->total;
        }

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate
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

    private static function fill_missing_days_custom($map, $startDate, $endDate)
    {
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate
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

    public static function execute_returns()
    {
        return new external_single_structure([
            'activity' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Usuarios activos')
                ])
            ),
            'abandon' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_FLOAT, '% abandono')
                ])
            ),
            'popularcourses' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Curso'),
                    'value' => new external_value(PARAM_INT, 'Usuarios activos')
                ])
            ),
            'teachers' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new external_value(PARAM_INT, 'Docentes activos')
                ])
            ),
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
            )
        ]);
    }
}