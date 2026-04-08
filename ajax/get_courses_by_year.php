<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

global $DB;

require_login();

// Contexto
$context = context_system::instance();

// Validación de permisos (ajústala si usas otra capability)
require_capability('local/dashboard_v3:view', $context);

// Parámetro seguro
$year = optional_param('year', 0, PARAM_INT);

// Respuesta JSON
header('Content-Type: application/json; charset=utf-8');

if ($year <= 0) {
    echo json_encode(['data' => []]);
    exit;
}

// Consulta
$sql = "
    SELECT 
        c.id,
        c.fullname,
        c.timecreated
    FROM {course} c
    WHERE YEAR(FROM_UNIXTIME(c.timecreated)) = :year
      AND c.id <> :siteid
      AND c.timecreated > 0
    ORDER BY c.timecreated DESC
";

$params = [
    'year' => $year,
    'siteid' => SITEID
];

$courses = $DB->get_records_sql($sql, $params);

// Formatear respuesta
$data = [];

foreach ($courses as $course) {
    $data[] = [
        'id' => (int)$course->id,
        'fullname' => format_string($course->fullname),
        'timecreated' => userdate($course->timecreated, '%Y-%m-%d')
    ];
}

// Output final
echo json_encode([
    'data' => $data
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

exit;