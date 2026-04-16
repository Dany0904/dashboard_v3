<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service_docentes {

    private static function format_insight($type, $message)
    {
        return [
            'type' => $type,
            'message' => $message,
            'is_danger' => $type === 'danger',
            'is_warning' => $type === 'warning',
            'is_info' => $type === 'info'
        ];
    }

    public static function get_teacher_kpis($days = 30)
    {
        global $DB;

        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $now = time();
        $start = $now - ($days * 86400);
        $prevstart = $now - ($days * 2 * 86400);
        $prevend = $start;

        // =========================
        // ROLES DOCENTE (FIX SQL SAFE)
        // =========================
        $teacherroleids = $DB->get_fieldset_sql("
            SELECT id
            FROM {role}
            WHERE shortname IN ('editingteacher', 'teacher')
        ");

        if (empty($teacherroleids)) {
            return [
                'kpis' => [],
                'insights' => []
            ];
        }

        list($rolein, $roleparams) = $DB->get_in_or_equal(
            $teacherroleids,
            SQL_PARAMS_NAMED,
            'r'
        );

        // base params
        $params_current = array_merge(['start' => $start], $roleparams);
        $params_previous = array_merge([
            'start' => $prevstart,
            'end' => $prevend
        ], $roleparams);

        // =========================
        // DOCENTES ACTIVOS
        // =========================
        $active_teachers = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND ra.roleid $rolein
        ", $params_current);

        $active_teachers_prev = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated BETWEEN :start AND :end
            AND ra.roleid $rolein
        ", $params_previous);

        // =========================
        // ACTIVIDAD TOTAL
        // =========================
        $activity = $DB->count_records_sql("
            SELECT COUNT(l.id)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND ra.roleid $rolein
        ", $params_current);

        $activity_prev = $DB->count_records_sql("
            SELECT COUNT(l.id)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated BETWEEN :start AND :end
            AND ra.roleid $rolein
        ", $params_previous);

        // =========================
        // PROMEDIO ACTIVIDAD
        // =========================
        $avg_activity = $active_teachers > 0 ? $activity / $active_teachers : 0;
        $avg_activity_prev = $active_teachers_prev > 0 ? $activity_prev / $active_teachers_prev : 0;

        // =========================
        // CURSOS CON INTERVENCIÓN
        // =========================
        $courses_intervened = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.courseid)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND l.courseid <> 0
            AND ra.roleid $rolein
        ", $params_current);

        $courses_intervened_prev = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.courseid)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.courseid <> 0
            AND ra.roleid $rolein
        ", $params_previous);

        // =========================
        // RETROALIMENTACIÓN
        // =========================
        $feedback = $DB->count_records_sql("
            SELECT COUNT(l.id)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND l.action IN ('created', 'updated')
            AND l.component IN ('mod_forum', 'core_grades')
            AND ra.roleid $rolein
        ", $params_current);

        $feedback_prev = $DB->count_records_sql("
            SELECT COUNT(l.id)
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.action IN ('created', 'updated')
            AND l.component IN ('mod_forum', 'core_grades')
            AND ra.roleid $rolein
        ", $params_previous);

        $feedback_rate = $activity > 0 ? ($feedback / $activity) * 100 : 0;
        $feedback_rate_prev = $activity_prev > 0 ? ($feedback_prev / $activity_prev) * 100 : 0;

        // =========================
        // CONSISTENCIA (DÍAS ACTIVOS)
        // =========================
        $days_active = $DB->count_records_sql("
            SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(l.timecreated)))
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND ra.roleid $rolein
        ", $params_current);

        $consistency = $active_teachers > 0 ? ($days_active / $days) * 100 : 0;

        // =========================
        // TREND
        // =========================
        $trend = function ($c, $p) {
            if ($p == 0) return $c > 0 ? 100 : 0;
            return round((($c - $p) / $p) * 100, 2);
        };

        // =========================
        // FORMAT KPI
        // =========================
        $format = function ($label, $value, $trend, $percent = false) {

            return [
                'label' => $label,
                'value' => $percent
                    ? number_format($value, 1) . '%'
                    : number_format($value, 1),
                'trend' => abs($trend),
                'trend_class' =>
                    $trend > 0 ? 'trend-up' :
                    ($trend < 0 ? 'trend-down' : 'trend-neutral'),
                'trend_label' => [
                    'up' => $trend > 0,
                    'down' => $trend < 0,
                    'equal' => $trend == 0
                ],
                'show_trend' => true
            ];
        };

        // =========================
        // INSIGHTS
        // =========================
        $insights = [];

        $t = $trend($active_teachers, $active_teachers_prev);

        if ($t <= -30) {
            $insights[] = self::format_insight(
                'danger',
                "Caída fuerte de docentes activos ($t%)"
            );
        }

        if ($t >= 30) {
            $insights[] = self::format_insight(
                'info',
                "Crecimiento de docentes activos +$t%"
            );
        }

        if ($feedback_rate < 20) {
            $insights[] = self::format_insight(
                'warning',
                "Baja retroalimentación docente (" . round($feedback_rate, 1) . "%)"
            );
        }

        if ($consistency < 40) {
            $insights[] = self::format_insight(
                'warning',
                "Baja consistencia docente"
            );
        }

        if ($active_teachers == 0) {
            $insights[] = self::format_insight(
                'danger',
                "No hay actividad docente en el periodo"
            );
        }

        // =========================
        // RETURN
        // =========================
        return [
            'kpis' => [
                $format('Docentes activos', $active_teachers, $trend($active_teachers, $active_teachers_prev)),
                $format('Actividad promedio', $avg_activity, $trend($avg_activity, $avg_activity_prev)),
                $format('Cursos con intervención', $courses_intervened, $trend($courses_intervened, $courses_intervened_prev)),
                $format('Tasa de retroalimentación', $feedback_rate, $trend($feedback_rate, $feedback_rate_prev), true),
                $format('Consistencia semanal', $consistency, 0, true)
            ],
            'insights' => $insights
        ];
    }
}