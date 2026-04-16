<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class course_table_service {

    public static function get_courses_ranking($days = 30)
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
        // CURSOS CON ACTIVIDAD
        // =========================
        $courses = $DB->get_records_sql("
            SELECT 
                c.id,
                c.fullname
            FROM {course} c
            WHERE c.id <> :siteid
        ", ['siteid' => SITEID]);

        $result = [];

        foreach ($courses as $c) {

            // =========================
            // EVENTOS (ACTUAL)
            // =========================
            $events = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                AND timecreated >= :start
            ", [
                'courseid' => $c->id,
                'start' => $start
            ]);

            // =========================
            // USUARIOS ACTIVOS
            // =========================
            $activeusers = $DB->count_records_sql("
                SELECT COUNT(DISTINCT userid)
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                AND timecreated >= :start
                AND userid > 0
            ", [
                'courseid' => $c->id,
                'start' => $start
            ]);

            // =========================
            // FINALIZACIONES
            // =========================
            $completions = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM {course_completions}
                WHERE course = :courseid
                AND timecompleted >= :start
            ", [
                'courseid' => $c->id,
                'start' => $start
            ]);

            $totalusers = max($DB->count_records_sql("
                SELECT COUNT(DISTINCT userid)
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                AND timecreated >= :start
            ", [
                'courseid' => $c->id,
                'start' => $start
            ]), 1);

            $completion_rate = ($completions / $totalusers) * 100;

            // =========================
            // PROMEDIO AVANCE (APPROX)
            // =========================
            $avg_progress = $activeusers > 0
                ? round(($events / $activeusers), 2)
                : 0;

            // =========================
            // TENDENCIA (EVENTOS)
            // =========================
            $prev_events = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                AND timecreated BETWEEN :start AND :end
            ", [
                'courseid' => $c->id,
                'start' => $prev_start,
                'end' => $prev_end
            ]);

            $trend = $prev_events > 0
                ? round((($events - $prev_events) / $prev_events) * 100, 2)
                : ($events > 0 ? 100 : 0);

            $result[] = [
                'fullname' => $c->fullname,
                'activeusers' => $activeusers,
                'events' => $events,
                'completion' => round($completion_rate, 2),
                'avg_progress' => $avg_progress,
                'trend' => $trend
            ];
        }

        // ordenar por actividad
        usort($result, fn($a, $b) => $b['events'] <=> $a['events']);

        return array_slice($result, 0, 20);
    }
}