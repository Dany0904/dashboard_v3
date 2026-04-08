<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

global $DB;

require_login();

$context = context_system::instance();
require_capability('local/dashboard_v3:view', $context);

header('Content-Type: application/json; charset=utf-8');

$id_cat = optional_param('idcategory', 0, PARAM_INT);

if ($id_cat <= 0) {
    echo json_encode(['data' => []]);
    exit;
}

$sql = "
    SELECT 
        c.id,
        c.fullname,
        c.startdate,
        c.enddate
    FROM {course} c
    WHERE c.category = :cat
      AND c.id <> :siteid
";

$params = [
    'cat' => $id_cat,
    'siteid' => SITEID
];

$courses = $DB->get_records_sql($sql, $params);

$data = [];

foreach ($courses as $c) {
    $data[] = [
        'id' => (int)$c->id,
        'fullname' => format_string($c->fullname),
        'startdate' => $c->startdate ? userdate($c->startdate, '%Y-%m-%d') : '',
        'enddate' => $c->enddate ? userdate($c->enddate, '%Y-%m-%d') : ''
    ];
}

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
exit;