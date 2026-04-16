<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service_docentes.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/teacher_table_service.php');

use local_dashboard_v3\service\kpi_service_docentes;
use local_dashboard_v3\service\teacher_table_service;

require_login();

$context = context_system::instance();
require_capability('local/dashboard_v3:view', $context);

$days = optional_param('days', 30, PARAM_INT);

$PAGE->set_url('/local/dashboard_v3/detalle_docentes.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Docentes');
$PAGE->set_heading('Docentes');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));
$PAGE->requires->js(new moodle_url('/local/dashboard_v3/js/apexcharts.min.js'), true);

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_docentes', 'init', [
    'days' => $days
]);

$data = kpi_service_docentes::get_teacher_kpis($days);
$table_teachers = teacher_table_service::get_teacher_ranking($days);

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
echo $renderer->sidebar('docentes');
echo '</div>';

echo $OUTPUT->render_from_template('local_dashboard_v3/detalle_docentes', [
    'kpis' => $data['kpis'],
    'insights' => $data['insights'],
    'table_teachers' => $renderer->render_teacher_ranking_table($table_teachers),
    'days' => $days,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo '</div>';

echo $OUTPUT->footer();