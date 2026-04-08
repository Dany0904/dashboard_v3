<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class kpi_service {

    public static function get_kpis_initial($days = 30) {
        global $DB;

        $now = time();
        $periodstart = $now - ($days * 86400);
        $previousstart = $periodstart - ($days * 86400);

        // =========================
        // 1. Usuarios activos
        // =========================
        $current_active = $DB->count_records_select(
            'user',
            'lastaccess > ?',
            [$periodstart]
        );

        $previous_active = $DB->count_records_select(
            'user',
            'lastaccess BETWEEN ? AND ?',
            [$previousstart, $periodstart]
        );

        $active_trend = self::calculate_trend($current_active, $previous_active);

        // =========================
        // 2. Cursos activos
        // =========================
       $sql = "
            SELECT COUNT(DISTINCT l.courseid)
            FROM {logstore_standard_log} l
            WHERE l.timecreated > :time
            AND l.courseid <> :siteid
        ";

        $current_courses = $DB->count_records_sql($sql, [
            'time' => $periodstart,
            'siteid' => SITEID
        ]);

        $previous_courses = $DB->count_records_sql($sql, [
            'time' => $previousstart,
            'siteid' => SITEID
        ]);

        $courses_trend = self::calculate_trend($current_courses, $previous_courses);

        // =========================
        // 3. Finalización vs abandono
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
                AND cc.timecompleted IS NOT NULL
        ";

        $data = $DB->get_record_sql($sql);

        $enrolled = $data->enrolled ?? 0;
        $completed = $data->completed ?? 0;

        $completion_rate = $enrolled > 0 ? ($completed / $enrolled) * 100 : 0;
        $dropout_rate = 100 - $completion_rate;

        return [
            [
                'label' => 'Usuarios activos',
                'value' => $current_active,
                'trend' => $active_trend['value'],
                'trend_class' => $active_trend['class']
            ],
            [
                'label' => 'Cursos activos',
                'value' => $current_courses,
                'trend' => $courses_trend['value'],
                'trend_class' => $courses_trend['class']
            ],
            [
                'label' => 'Tasa finalización',
                'value' => round($completion_rate, 1) . '%',
                'trend' => '',
                'trend_class' => ''
            ],
            [
                'label' => 'Tasa abandono',
                'value' => round($dropout_rate, 1) . '%',
                'trend' => '',
                'trend_class' => ''
            ]
        ];
    }

    private static function calculate_trend($current, $previous) {

        if ($previous == 0) {
            return [
                'value' => 0,
                'class' => ''
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => round($change, 1),
            'class' => $change >= 0 ? 'text-success' : 'text-danger'
        ];
    }
}