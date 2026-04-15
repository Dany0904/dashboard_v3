<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service_usuarios {
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

    public static function get_user_kpis($days = 30, $courseid = 0)
    {
        global $DB;

        // =========================
        // VALIDACIÓN
        // =========================
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        // =========================
        // RANGOS DE FECHA
        // =========================
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
        // ACTIVOS (ACTUAL)
        // =========================
        $totalactive = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated >= :start
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            AND l.edulevel > 0
            $coursefilter
        ", $params_current);

        // =========================
        // ACTIVOS (ANTERIOR)
        // =========================
        $totalactive_prev = $DB->count_records_sql("
            SELECT COUNT(DISTINCT l.userid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated BETWEEN :start AND :end
            AND l.userid IS NOT NULL
            AND l.courseid IS NOT NULL
            AND l.courseid <> 0
            AND l.edulevel > 0
            $coursefilter
        ", $params_previous);

        // =========================
        // NUEVOS (NO depende de curso)
        // =========================
        $newcurrent = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {user}
            WHERE timecreated >= :start
            AND deleted = 0
        ", ['start' => $currentstart]);

        $newprevious = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {user}
            WHERE timecreated BETWEEN :start AND :end
            AND deleted = 0
        ", [
            'start' => $previousstart,
            'end' => $previousend
        ]);

        // =========================
        // TOTAL USERS
        // =========================
        $totalusers = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {user}
            WHERE deleted = 0
            AND suspended = 0
        ");

        // =========================
        // DERIVADOS
        // =========================
        $recurrent = max($totalactive - $newcurrent, 0);
        $recurrent_prev = max($totalactive_prev - $newprevious, 0);

        $inactive = max($totalusers - $totalactive, 0);
        $inactive_prev = max($totalusers - $totalactive_prev, 0);

        $engagement = $totalusers > 0
            ? ($totalactive / $totalusers) * 100
            : 0;

        $engagement_prev = $totalusers > 0
            ? ($totalactive_prev / $totalusers) * 100
            : 0;

        // =========================
        // FUNCIÓN TREND
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
        $format_kpi = function ($label, $value, $trend) {

            $trend_class = 'trend-neutral';
            $trend_label = [
                'up' => false,
                'down' => false,
                'equal' => false
            ];

            if ($trend > 0) {
                $trend_class = 'trend-up';
                $trend_label['up'] = true;
            } elseif ($trend < 0) {
                $trend_class = 'trend-down';
                $trend_label['down'] = true;
            } else {
                $trend_label['equal'] = true;
            }

            return [
                'label' => $label,
                'value' => is_numeric($value) ? number_format($value) : $value,
                'trend' => abs($trend),
                'trend_class' => $trend_class,
                'trend_label' => $trend_label,
                'show_trend' => true
            ];
        };

        // =========================
        // KPIs
        // =========================
        $kpis = [
            $format_kpi('Usuarios activos', $totalactive, $calc_trend($totalactive, $totalactive_prev)),
            $format_kpi('Usuarios nuevos', $newcurrent, $calc_trend($newcurrent, $newprevious)),
            $format_kpi('Usuarios recurrentes', $recurrent, $calc_trend($recurrent, $recurrent_prev)),
            $format_kpi('Usuarios inactivos', $inactive, $calc_trend($inactive, $inactive_prev)),
            $format_kpi('Engagement', round($engagement, 2) . '%', $calc_trend($engagement, $engagement_prev))
        ];

        // =========================
        // INSIGHTS INTELIGENTES (USUARIOS)
        // =========================
        $insights = [];

        $trend_active = $calc_trend($totalactive, $totalactive_prev);

        // 1. Caída fuerte de actividad
        if ($trend_active <= -30) {
            $insights[] = self::format_insight(
                'danger',
                "Los usuarios activos cayeron " . abs($trend_active) . "% vs periodo anterior"
            );
        }

        // 2. Crecimiento fuerte
        if ($trend_active >= 30) {
            $insights[] = self::format_insight(
                'info',
                "Crecimiento positivo: usuarios activos +" . $trend_active . "%"
            );
        }

        // 3. Alto porcentaje de inactivos
        $inactive_ratio = $totalusers > 0
            ? ($inactive / $totalusers) * 100
            : 0;

        if ($inactive_ratio > 60) {
            $insights[] = self::format_insight(
                'warning',
                round($inactive_ratio, 1) . "% de usuarios están inactivos"
            );
        }

        // 4. Dependencia de usuarios nuevos
        if ($totalactive > 0) {
            $new_ratio = ($newcurrent / $totalactive) * 100;

            if ($new_ratio > 70) {
                $insights[] = self::format_insight(
                    'warning',
                    "Alta dependencia de usuarios nuevos (" . round($new_ratio, 1) . "%)"
                );
            }
        }

        // 5. Baja recurrencia
        if ($totalactive > 0) {
            $recurrent_ratio = ($recurrent / $totalactive) * 100;

            if ($recurrent_ratio < 40) {
                $insights[] = self::format_insight(
                    'warning',
                    "Baja recurrencia: solo " . round($recurrent_ratio, 1) . "% regresan"
                );
            }
        }

        // 6. Engagement bajo
        if ($engagement < 20) {
            $insights[] = self::format_insight(
                'danger',
                "Engagement crítico (" . round($engagement, 1) . "%)"
            );
        }

        // 7. Actividad muy baja
        if ($totalactive < 5) {
            $insights[] = self::format_insight(
                'warning',
                "Muy pocos usuarios activos en el periodo"
            );
        }

        // 8. Sin actividad en curso específico
        if (!empty($courseid) && $totalactive == 0) {
            $insights[] = self::format_insight(
                'danger',
                "Este curso no tiene actividad en el periodo seleccionado"
            );
        }

        // 9. Buen engagement
        if ($engagement > 60) {
            $insights[] = self::format_insight(
                'info',
                "Buen nivel de engagement (" . round($engagement, 1) . "%)"
            );
        }

        // 10. Base sólida
        if ($recurrent > $newcurrent) {
            $insights[] = self::format_insight(
                'info',
                "Base sólida: más usuarios recurrentes que nuevos"
            );
        }

        return [
            'kpis' => $kpis,
            'insights' => $insights
        ];
    }
}