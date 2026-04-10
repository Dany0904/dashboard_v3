<?php

namespace local_dashboard_v3\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

// Importar los nuevos endpoints
use local_dashboard_v3\external\get_dashboard_v3_charts;
use local_dashboard_v3\external\get_dashboard_v3_tables;

class get_dashboard_v3_data extends external_api
{
    public static function execute_parameters() {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT, 'Rango de días', VALUE_DEFAULT, 30)
        ]);
    }

    public static function execute($days = 30)
    {
        self::validate_context(\context_system::instance());

        // Validar días
        $allowed = [7, 30, 90];
        if (!in_array($days, $allowed)) {
            $days = 30;
        }

        // 👇 Llamar a los nuevos servicios
        $charts = get_dashboard_v3_charts::execute($days);
        $tables = get_dashboard_v3_tables::execute($days);

        // 👇 Merge de respuestas
        return array_merge($charts, $tables);
    }

    public static function execute_returns()
    {
        //  Reutilizamos estructuras de ambos
        return new external_single_structure([

            // CHARTS
            'users' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'label' => new \core_external\external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new \core_external\external_value(PARAM_INT, 'Total')
                ])
            ),

            'courses' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'label' => new \core_external\external_value(PARAM_TEXT, 'Fecha'),
                    'value' => new \core_external\external_value(PARAM_INT, 'Total')
                ])
            ),

            'categories' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'label' => new \core_external\external_value(PARAM_TEXT, 'Categoría'),
                    'value' => new \core_external\external_value(PARAM_INT, 'Total')
                ])
            ),

            // TABLES
            'enrollments' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'name' => new \core_external\external_value(PARAM_TEXT, 'Curso'),
                    'total' => new \core_external\external_value(PARAM_INT, 'Inscripciones')
                ])
            ),

            'averages' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'name' => new \core_external\external_value(PARAM_TEXT, 'Curso'),
                    'average' => new \core_external\external_value(PARAM_FLOAT, 'Promedio')
                ])
            ),

            'activitycourses' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'name' => new \core_external\external_value(PARAM_TEXT, 'Curso'),
                    'total' => new \core_external\external_value(PARAM_INT, 'Usuarios activos')
                ])
            ),

            'progress' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'fullname' => new \core_external\external_value(PARAM_TEXT, 'Curso'),
                    'progress' => new \core_external\external_value(PARAM_FLOAT, 'Progreso')
                ])
            ),

            'vs' => new \core_external\external_multiple_structure(
                new \core_external\external_single_structure([
                    'fullname' => new \core_external\external_value(PARAM_TEXT, 'Curso'),
                    'completed' => new \core_external\external_value(PARAM_INT, 'Completados'),
                    'notcompleted' => new \core_external\external_value(PARAM_INT, 'No completados')
                ])
            )
        ]);
    }
}