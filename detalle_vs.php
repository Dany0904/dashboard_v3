<?php

use local_dashboard_v3\table\detalle_vs_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_vs.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Finalizado vs No Finalizado');
$PAGE->set_heading('Finalizado vs No Finalizado');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');

//  TABLA MOODLE
$table = new detalle_vs_table('detallevs');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'reporte_finalizacion_cursos', 'Finalización de Cursos');

$table->set_sql(
    'c.id, cat.name AS category, c.fullname, 
     COUNT(DISTINCT u.id) AS participants,
     COUNT(DISTINCT CASE WHEN cc.timecompleted IS NOT NULL THEN u.id END) AS completed,
     (COUNT(DISTINCT u.id) - COUNT(DISTINCT CASE WHEN cc.timecompleted IS NOT NULL THEN u.id END)) AS notcompleted',
    '{course} c
     JOIN {course_categories} cat ON cat.id = c.category
     LEFT JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
     LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
     LEFT JOIN {user} u ON u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0
     LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id',
    'c.category > 0 GROUP BY c.id, c.fullname, cat.name'
);

//  SI ES DESCARGA → SOLO EXCEL
if ($table->is_downloading()) {
    $table->out(0, false);
    exit;
}

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_dashboard_v3');

echo '<div class="d-flex flex-column flex-md-row">';

echo '
<button type="button"
        class="btn btn-primary d-md-none mb-2"
        data-toggle="collapse"
        data-target="#sidebarMenu">
    ☰ Menú
</button>
';
echo '<div class="collapse d-md-block local-dashboard-sidebar" id="sidebarMenu">';
echo $renderer->sidebar('index');
echo '</div>';

// Contenido
echo '<div class="flex-fill p-3">';

echo '<h4>Finalizado vs No Finalizado</h4>';

$table->set_attribute('class', 'generaltable table local-dashboard-table');
$table->out(10, false); // 10 registros por página

echo '</div>'; // contenido

echo '</div>'; // layout

echo '</div>';

echo $OUTPUT->footer(); 