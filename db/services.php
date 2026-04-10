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
];