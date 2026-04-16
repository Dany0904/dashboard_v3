<?php

namespace local_dashboard_v3\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_teacher_active_daily extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT)
        ]);
    }

    public static function execute($days) {
        global $DB;

        // =========================
        // VALIDACIÓN
        // =========================
        $params = self::validate_parameters(self::execute_parameters(), [
            'days' => $days
        ]);

        $days = in_array($params['days'], [7, 30, 90]) ? $params['days'] : 30;

        $now = time();
        $start = $now - ($days * 86400);

        // =========================
        // ROLES DOCENTE (FIX TIPOS)
        // =========================
        $roles = $DB->get_fieldset_sql("
            SELECT id
            FROM {role}
            WHERE shortname IN ('editingteacher', 'teacher')
        ");

        if (empty($roles)) {
            return ['data' => []];
        }

        // FIX CRÍTICO: forzar INT
        $roles = array_map('intval', $roles);

        // 🔥 FIX CORRECTO MOODLE: named params
        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r');

        // =========================
        // QUERY PRINCIPAL
        // =========================
        $records = $DB->get_records_sql("
            SELECT
                DATE(FROM_UNIXTIME(l.timecreated)) as day,
                COUNT(DISTINCT l.userid) as total
            FROM {logstore_standard_log} l
            JOIN {role_assignments} ra ON ra.userid = l.userid
            WHERE l.timecreated >= :start
            AND ra.roleid $insql
            GROUP BY day
            ORDER BY day ASC
        ", array_merge(['start' => $start], $inparams));

        // =========================
        // MAPEAR RESULTADOS
        // =========================
        $map = [];

        foreach ($records as $r) {
            $map[$r->day] = (int)$r->total;
        }

        // =========================
        // RELLENAR DÍAS FALTANTES
        // =========================
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d', $start)),
            new \DateInterval('P1D'),
            (new \DateTime(date('Y-m-d', $now)))->modify('+1 day')
        );

        $result = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');

            $result[] = [
                'label' => $date->format('d M'),
                'value' => $map[$key] ?? 0
            ];
        }

        return ['data' => $result];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT),
                    'value' => new external_value(PARAM_INT)
                ])
            )
        ]);
    }
}