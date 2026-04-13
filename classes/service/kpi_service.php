<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service {

    public static function get_kpis_initial($days = 30) {
        global $DB;

        $now = time();
        $current_start = $now - ($days * DAYSECS);
        $previous_start = $current_start - ($days * DAYSECS);

        // =========================
        // 1. Usuarios activos (logs)
        // =========================
        $sql = "
            SELECT COUNT(DISTINCT userid)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND userid > 0
        ";

        $current_active_users = $DB->get_field_sql($sql, [
            'start' => $current_start,
            'end' => $now
        ]);

        $previous_active_users = $DB->get_field_sql($sql, [
            'start' => $previous_start,
            'end' => $current_start
        ]);

        $active_users_trend = self::calculate_trend($current_active_users, $previous_active_users);

        // =========================
        // 2. Cursos activos
        // =========================
        $sql = "
            SELECT COUNT(DISTINCT courseid)
            FROM {logstore_standard_log}
            WHERE timecreated BETWEEN :start AND :end
            AND courseid > 1
        ";

        $current_active_courses = $DB->get_field_sql($sql, [
            'start' => $current_start,
            'end' => $now
        ]);

        $previous_active_courses = $DB->get_field_sql($sql, [
            'start' => $previous_start,
            'end' => $current_start
        ]);

        $active_courses_trend = self::calculate_trend($current_active_courses, $previous_active_courses);

        // Total cursos visibles
        $total_courses = $DB->count_records_select('course', 'visible = 1 AND id > 1');

        // =========================
        // 3. Tasa de finalización
        // =========================
        $sql = "
            SELECT
                COUNT(DISTINCT ue.userid) AS enrolled,
                COUNT(DISTINCT cc.userid) AS completed
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            LEFT JOIN {course_completions} cc 
                ON cc.userid = ue.userid
                AND cc.course = e.courseid
                AND cc.timecompleted BETWEEN :start AND :end
        ";

        $completion_data = $DB->get_record_sql($sql, [
            'start' => $current_start,
            'end' => $now
        ]);

        $enrolled = $completion_data->enrolled ?? 0;
        $completed = $completion_data->completed ?? 0;

        $completion_rate = $enrolled > 0
            ? round(($completed / $enrolled) * 100, 1)
            : 0;

        $sql = "
            SELECT
                COUNT(DISTINCT ue.userid) AS enrolled,
                COUNT(DISTINCT cc.userid) AS completed
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            LEFT JOIN {course_completions} cc 
                ON cc.userid = ue.userid
                AND cc.course = e.courseid
                AND cc.timecompleted BETWEEN :start AND :end
        ";

        $previous_completion_data = $DB->get_record_sql($sql, [
            'start' => $previous_start,
            'end' => $current_start
        ]);

        $prev_enrolled = $previous_completion_data->enrolled ?? 0;
        $prev_completed = $previous_completion_data->completed ?? 0;

        $previous_completion_rate = $prev_enrolled > 0
            ? ($prev_completed / $prev_enrolled) * 100
            : 0;

        $completion_trend = self::calculate_trend(
            $completion_rate,
            $previous_completion_rate
        );

        // =========================
        // 4. Tiempo promedio
        // =========================
        $sql = "
            SELECT AVG(sessiontime)
            FROM (
                SELECT userid,
                       MAX(timecreated) - MIN(timecreated) AS sessiontime
                FROM {logstore_standard_log}
                WHERE timecreated > :start
                AND userid > 0
                AND courseid > 1
                GROUP BY userid
            ) t
        ";

        $avg_time = (int)$DB->get_field_sql($sql, [
            'start' => $current_start
        ]);

        // =========================
        // 5. Baja participación
        // =========================
        $sql = "
            SELECT COUNT(*)
            FROM (
                SELECT courseid, COUNT(*) as total
                FROM {logstore_standard_log}
                WHERE timecreated > :start
                AND courseid > 1
                GROUP BY courseid
            ) t
            WHERE total < (
                SELECT AVG(total)
                FROM (
                    SELECT COUNT(*) as total
                    FROM {logstore_standard_log}
                    WHERE timecreated > :start2
                    AND courseid > 1
                    GROUP BY courseid
                ) x
            )
        ";

        $low_participation = $DB->get_field_sql($sql, [
            'start' => $current_start,
            'start2' => $current_start
        ]);

        // =========================
        // INSIGHTS INTELIGENTES
        // =========================
        $insights = [];

        // 1. Caída de usuarios
        if ($active_users_trend['value'] <= -30) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Caída significativa en usuarios activos'
            ];
        }

        // 2. Pocos cursos activos
        $active_ratio = $total_courses > 0
            ? ($current_active_courses / $total_courses) * 100
            : 0;

        if ($active_ratio < 50) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Menos del 50% de cursos tienen actividad reciente'
            ];
        }

        // 3. Baja participación
        if ($low_participation > 0) {
            $insights[] = [
                'type' => 'warning',
                'message' => "$low_participation cursos con baja participación"
            ];
        }

        // 4. Sin finalizaciones
        if ($completion_rate == 0) {
            $insights[] = [
                'type' => 'info',
                'message' => 'No hay cursos finalizados en el periodo'
            ];
        }

        // 5. Poco uso general
        if ($current_active_users < 5) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Muy baja actividad en la plataforma'
            ];
        }

        usort($insights, function($a, $b) {

            $priority = [
                'danger' => 1,
                'warning' => 2,
                'info' => 3
            ];

            return ($priority[$a['type']] ?? 99) <=> ($priority[$b['type']] ?? 99);
        });

        return [
            'kpis' => [
                [
                    'label' => 'Usuarios activos',
                    'value' => $current_active_users,
                    'trend' => $active_users_trend['value'],
                    'show_trend' => true,
                    'trend_class' => $active_users_trend['class'],
                    'trend_label' => $active_users_trend['trend_label']
                ],
                [
                    'label' => 'Cursos activos',
                    'value' => $current_active_courses,
                    'trend' => $active_courses_trend['value'],
                    'show_trend' => true,
                    'trend_class' => $active_courses_trend['class'],
                    'trend_label' => $active_courses_trend['trend_label']
                ],
                [
                    'label' => 'Tasa de finalización',
                    'value' => $completion_rate . '%',
                    'trend' => $completion_trend['value'],
                    'show_trend' => true,
                    'trend_class' => $completion_trend['class'],
                    'trend_label' => $completion_trend['trend_label']
                ],
                [
                    'label' => 'Tiempo promedio',
                    'value' => $avg_time > 0 ? gmdate("H:i", $avg_time) : '00:00'
                ],
                [
                    'label' => 'Cursos con baja participación',
                    'value' => $low_participation
                ]
            ],
            'insights' => $insights
        ];
    }

    private static function calculate_trend($current, $previous) {

        if ($previous <= 0) {
            return [
                'value' => 0,
                'class' => 'text-muted',
                'trend_label' => [
                    'equal' => true
                ]
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $rounded = round($change, 1);

        if ($rounded > 0) {
            return [
                'value' => '+' . $rounded,
                'class' => 'text-success',
                'trend_label' => [
                    'up' => true
                ]
            ];
        } elseif ($rounded < 0) {
            return [
                'value' => $rounded,
                'class' => 'text-danger',
                'trend_label' => [
                    'down' => true
                ]
            ];
        } else {
            return [
                'value' => '0',
                'class' => 'text-muted',
                'trend_label' => [
                    'equal' => true
                ]
            ];
        }
    }
}