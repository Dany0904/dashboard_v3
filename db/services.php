<?php

$functions = [
    'local_dashboard_v3_get_dashboard_v3_data' => [
        'classname'   => 'local_dashboard_v3\external\get_dashboard_v3_data',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get dashboard_v3 data (merged)',
        'type'        => 'read',
        'ajax'        => true,
    ],

    // charts
    'local_dashboard_v3_get_dashboard_v3_charts' => [
        'classname'   => 'local_dashboard_v3\external\get_dashboard_v3_charts',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get dashboard charts',
        'type'        => 'read',
        'ajax'        => true,
    ],

    // tables
    'local_dashboard_v3_get_dashboard_v3_tables' => [
        'classname'   => 'local_dashboard_v3\external\get_dashboard_v3_tables',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get dashboard tables',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'local_dashboard_v3_get_user_activity_chart' => [
        'classname'   => 'local_dashboard_v3\external\get_user_activity_chart',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Obtiene actividad de usuarios (actual vs anterior)',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'local_dashboard_v3_get_user_segmentation' => [
        'classname'   => 'local_dashboard_v3\external\get_user_segmentation',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Obtiene distribución de usuarios (activos, nuevos, recurrentes, inactivos)',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'local_dashboard_v3_get_course_activity_chart' => [
        'classname' => 'local_dashboard_v3\external\get_course_activity_chart',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Actividad de cursos (línea)',
        'type' => 'read',
        'ajax' => true,
    ]
];