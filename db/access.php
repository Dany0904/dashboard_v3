<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/dashboard_v3:view' => [
        'riskbitmask' => RISK_PERSONAL,

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,

        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ]
    ],
];