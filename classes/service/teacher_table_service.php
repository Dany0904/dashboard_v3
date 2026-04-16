<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class teacher_table_service {

    public static function get_teacher_ranking($days = 30)
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
        // ROLES DOCENTE
        // =========================
        $roles = $DB->get_fieldset_sql("
            SELECT id FROM {role}
            WHERE shortname IN ('editingteacher', 'teacher')
        ");

        if (empty($roles)) {
            return [];
        }

        [$in, $params_roles] = $DB->get_in_or_equal($roles);

        // =========================
        // USUARIOS DOCENTES
        // =========================
        $teachers = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            WHERE ra.roleid $in
            AND u.deleted = 0
        ", $params_roles);

        $result = [];

        foreach ($teachers as $t) {

            // =========================
            // ACTIVIDAD
            // =========================
            $events = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM {logstore_standard_log}
                WHERE userid = :userid
                AND timecreated >= :start
            ", [
                'userid' => $t->id,
                'start' => $start
            ]);

            $prev_events = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM {logstore_standard_log}
                WHERE userid = :userid
                AND timecreated BETWEEN :start AND :end
            ", [
                'userid' => $t->id,
                'start' => $prev_start,
                'end' => $prev_end
            ]);

            // =========================
            // CURSOS INTERVENIDOS
            // =========================
            $courses = $DB->count_records_sql("
                SELECT COUNT(DISTINCT courseid)
                FROM {logstore_standard_log}
                WHERE userid = :userid
                AND courseid <> 0
                AND timecreated >= :start
            ", [
                'userid' => $t->id,
                'start' => $start
            ]);

            // =========================
            // ÚLTIMA ACTIVIDAD
            // =========================
            $last = $DB->get_field_sql("
                SELECT MAX(timecreated)
                FROM {logstore_standard_log}
                WHERE userid = :userid
            ", ['userid' => $t->id]);

            // =========================
            // PROMEDIO DIARIO
            // =========================
            $avg_daily = $days > 0 ? round($events / $days, 2) : 0;

            // =========================
            // TENDENCIA
            // =========================
            $trend = $prev_events > 0
                ? round((($events - $prev_events) / $prev_events) * 100, 2)
                : ($events > 0 ? 100 : 0);

            $result[] = [
                'fullname' => $t->firstname . ' ' . $t->lastname,
                'events' => $events,
                'courses' => $courses,
                'lastactivity' => $last ? date('d M Y H:i', $last) : '-',
                'avg_daily' => $avg_daily,
                'trend' => $trend
            ];
        }

        usort($result, fn($a, $b) => $b['events'] <=> $a['events']);

        return array_slice($result, 0, 20);
    }
}