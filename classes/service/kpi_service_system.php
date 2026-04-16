<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service_system {

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

    public static function get_system_kpis($days = 30)
    {
        global $DB;

        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $now = time();

        $start = $now - ($days * 86400);
        $prev_start = $now - ($days * 2 * 86400);
        $prev_end = $start;

        // =========================
        // 1. UPTIME (aproximado por logs sin errores críticos)
        // =========================
        $total_checks = max($DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
        ", ['start' => $start]), 1);

        $error_logs = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            AND (action LIKE '%error%' OR action LIKE '%fail%' OR action LIKE '%exception%')
        ", ['start' => $start]);

        $uptime = max(100 - (($error_logs / $total_checks) * 100), 0);

        $error_logs_prev = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND (action LIKE '%error%' OR action LIKE '%fail%' OR action LIKE '%exception%')
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]);

        $total_checks_prev = max($DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]), 1);

        $uptime_prev = max(100 - (($error_logs_prev / $total_checks_prev) * 100), 0);

        // =========================
        // 2. TIEMPO DE RESPUESTA (aprox log actions por segundo)
        // =========================
        $requests = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
        ", ['start' => $start]);

        $avg_response = $days > 0 ? round(($requests / ($days * 86400)) * 1000, 2) : 0;

        $requests_prev = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]);

        $avg_response_prev = $days > 0 ? round(($requests_prev / ($days * 86400)) * 1000, 2) : 0;

        // =========================
        // 3. USUARIOS ACTIVOS SISTEMA
        // =========================
        $active_users = $DB->count_records_sql("
            SELECT COUNT(DISTINCT userid)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            AND userid > 0
        ", ['start' => $start]);

        $active_users_prev = $DB->count_records_sql("
            SELECT COUNT(DISTINCT userid)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND userid > 0
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]);

        // =========================
        // 4. CARGA DE PÁGINAS
        // =========================
        $page_views = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            AND action = 'viewed'
        ", ['start' => $start]);

        $page_views_prev = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND action = 'viewed'
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]);

        $pages_per_day = $days > 0 ? round($page_views / $days, 2) : 0;
        $pages_per_day_prev = $days > 0 ? round($page_views_prev / $days, 2) : 0;

        // =========================
        // 5. ERRORES CRÍTICOS
        // =========================
        $critical_errors = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated >= :start
            AND (
                action LIKE '%error%'
                OR action LIKE '%exception%'
                OR action LIKE '%fail%'
            )
        ", ['start' => $start]);

        $critical_errors_prev = $DB->count_records_sql("
            SELECT COUNT(1)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND (
                action LIKE '%error%'
                OR action LIKE '%exception%'
                OR action LIKE '%fail%'
            )
        ", [
            'start' => $prev_start,
            'end' => $prev_end
        ]);

        // =========================
        // TREND HELPER
        // =========================
        $trend = function($c, $p) {
            if ($p == 0) return $c > 0 ? 100 : 0;
            return round((($c - $p) / $p) * 100, 2);
        };

        // =========================
        // FORMAT KPI
        // =========================
        $format = function($label, $value, $trend, $percent = false) {

            return [
                'label' => $label,
                'value' => $percent ? number_format($value, 2) . '%' : number_format($value, 2),
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

        if ($critical_errors > 50) {
            $insights[] = self::format_insight(
                'danger',
                "Alta cantidad de errores críticos detectados ($critical_errors)"
            );
        }

        if ($uptime < 95) {
            $insights[] = self::format_insight(
                'warning',
                "Uptime bajo: " . round($uptime, 2) . "%"
            );
        }

        if ($avg_response > 5000) {
            $insights[] = self::format_insight(
                'danger',
                "Tiempo de respuesta alto: {$avg_response} ms"
            );
        }

        if ($active_users > $active_users_prev * 1.5) {
            $insights[] = self::format_insight(
                'info',
                "Incremento fuerte de usuarios activos en el sistema"
            );
        }

        return [
            'kpis' => [
                $format('Uptime del sistema', $uptime, $trend($uptime, $uptime_prev), true),
                $format('Tiempo de respuesta (ms)', $avg_response, $trend($avg_response, $avg_response_prev)),
                $format('Usuarios activos', $active_users, $trend($active_users, $active_users_prev)),
                $format('Páginas por día', $pages_per_day, $trend($pages_per_day, $pages_per_day_prev)),
                $format('Errores críticos', $critical_errors, $trend($critical_errors, $critical_errors_prev))
            ],
            'insights' => $insights
        ];
    }
}