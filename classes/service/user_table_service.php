<?php

namespace local_dashboard_v3\service;

defined('MOODLE_INTERNAL') || die();

class user_table_service {

    public static function get_top_users($days = 30, $courseid = 0)
    {
        global $DB;

        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        $start = time() - ($days * 86400);

        $params = ['start' => $start];
        $coursefilter = '';

        if (!empty($courseid)) {
            $coursefilter = "AND l.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // =========================
        // QUERY PRINCIPAL
        // =========================
        $sql = "
            SELECT 
                u.id,
                CONCAT(u.firstname, ' ', u.lastname) as fullname,
                COUNT(l.id) as accesses,
                MAX(l.timecreated) as lastaccess
            FROM {logstore_standard_log} l
            JOIN {user} u ON u.id = l.userid
            WHERE l.timecreated >= :start
            AND u.deleted = 0
            AND l.userid IS NOT NULL
            AND l.edulevel > 0
            $coursefilter
            GROUP BY u.id, u.firstname, u.lastname
            ORDER BY accesses DESC
            LIMIT 20
        ";

        $users = $DB->get_records_sql($sql, $params);

        // =========================
        // AGREGAR CURSOS ACTIVOS
        // =========================
        $result = [];

        foreach ($users as $u) {

            $courses = $DB->count_records_sql("
                SELECT COUNT(DISTINCT l.courseid)
                FROM {logstore_standard_log} l
                WHERE l.userid = :userid
                AND l.timecreated >= :start
                AND l.courseid <> 0
            ", [
                'userid' => $u->id,
                'start' => $start
            ]);

            $result[] = [
                'fullname' => $u->fullname,
                'accesses' => (int)$u->accesses,
                'lastaccess' => userdate($u->lastaccess),
                'courses' => $courses
            ];
        }

        return $result;
    }
}