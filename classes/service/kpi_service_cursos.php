<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service_cursos {

    public static function get_course_kpis($days = 30, $courseid = 0)
    {
        global $DB;

        // =========================
        // VALIDACIÓN
        // =========================
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $now = time();

        $currentstart = $now - ($days * 86400);
        $previousstart = $now - ($days * 2 * 86400);
        $previousend = $currentstart;

        // =========================
        // FILTRO CURSO
        // =========================
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
        // TOTAL CURSOS
        // =========================
        $totalcourses = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {course}
            WHERE id <> :siteid
        ", ['siteid' => SITEID]);

        // =========================
        // USUARIOS ÚNICOS EN CURSOS
        // =========================
        $users_current = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            $coursefilter
        ", $params_current);

        $users_previous = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.courseid <> 0
            $coursefilter
        ", $params_previous);

        // =========================
        // CURSOS ACTIVOS
        // =========================
        $activecourses = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.courseid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            $coursefilter
        ", $params_current);

        $activecourses_prev = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.courseid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.courseid <> 0
            $coursefilter
        ", $params_previous);

        // =========================
        // USUARIOS POR CURSO (PROMEDIO)
        // =========================
        $users_per_course = $activecourses > 0
            ? $users_current / $activecourses
            : 0;

        $users_per_course_prev = $activecourses_prev > 0
            ? $users_previous / $activecourses_prev
            : 0;

        // =========================
        // CURSOS CON ACTIVIDAD (%)
        // =========================
        $courses_activity = $totalcourses > 0
            ? ($activecourses / $totalcourses) * 100
            : 0;

        $courses_activity_prev = $totalcourses > 0
            ? ($activecourses_prev / $totalcourses) * 100
            : 0;

        // =========================
        // PROMEDIO DE AVANCE (%)
        // =========================
        $progress_current = $DB->get_record_sql("
            SELECT AVG(progress) as avgprogress
            FROM (
                SELECT cmc.userid,
                       (SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(cmc.id)) as progress
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                WHERE cmc.timemodified >= :start
                " . (!empty($courseid) ? "AND cm.course = :courseid" : "") . "
                GROUP BY cmc.userid
            ) t
        ", $params_current);

        $progress_prev = $DB->get_record_sql("
            SELECT AVG(progress) as avgprogress
            FROM (
                SELECT cmc.userid,
                       (SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(cmc.id)) as progress
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                WHERE cmc.timemodified BETWEEN :start AND :end
                " . (!empty($courseid) ? "AND cm.course = :courseid" : "") . "
                GROUP BY cmc.userid
            ) t
        ", $params_previous);

        $avg_progress = $progress_current->avgprogress ?? 0;
        $avg_progress_prev = $progress_prev->avgprogress ?? 0;

        // =========================
        // FINALIZACIÓN
        // =========================
        $completion = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {course_completions}
            WHERE timecompleted >= :start
            " . (!empty($courseid) ? "AND course = :courseid" : "") . "
        ", $params_current);

        $completion_prev = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {course_completions}
            WHERE timecompleted BETWEEN :start AND :end
            " . (!empty($courseid) ? "AND course = :courseid" : "") . "
        ", $params_previous);

        $completion_rate = $users_current > 0
            ? ($completion / $users_current) * 100
            : 0;

        $completion_rate_prev = $users_previous > 0
            ? ($completion_prev / $users_previous) * 100
            : 0;

        // =========================
        // TASA DE ABANDONO IMPLÍCITA (%)
        // =========================
        $dropoff = max(100 - $avg_progress, 0);
        $dropoff_prev = max(100 - $avg_progress_prev, 0);

        // =========================
        // TREND
        // =========================
        $calc_trend = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 2);
        };

        // =========================
        // FORMATO KPI
        // =========================
        $format = function ($label, $value, $trend, $ispercent = false) {

            $class = 'trend-neutral';
            $trend_label = ['up' => false, 'down' => false, 'equal' => false];

            if ($trend > 0) {
                $class = 'trend-up';
                $trend_label['up'] = true;
            } elseif ($trend < 0) {
                $class = 'trend-down';
                $trend_label['down'] = true;
            } else {
                $trend_label['equal'] = true;
            }

            return [
                'label' => $label,
                'value' => $ispercent
                    ? number_format($value, 1) . '%'
                    : number_format($value, 1),
                'trend' => abs($trend),
                'trend_class' => $class,
                'trend_label' => $trend_label,
                'show_trend' => true
            ];
        };

        // =========================
        // KPIs
        // =========================
        return [
            $format('Usuarios por curso (promedio)', $users_per_course, $calc_trend($users_per_course, $users_per_course_prev)),
            $format('Cursos con actividad', $courses_activity, $calc_trend($courses_activity, $courses_activity_prev), true),
            $format('Promedio de avance', $avg_progress, $calc_trend($avg_progress, $avg_progress_prev), true),
            $format('Tasa de finalización', $completion_rate, $calc_trend($completion_rate, $completion_rate_prev), true),
            $format('Tasa de abandono', $dropoff, $calc_trend($dropoff, $dropoff_prev), true)
        ];
    }
}