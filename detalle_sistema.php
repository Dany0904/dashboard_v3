<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service_system.php');

use local_dashboard_v3\service\kpi_service_system;

require_login();

$context = context_system::instance();
require_capability('local/dashboard_v3:view', $context);

$days = optional_param('days', 30, PARAM_INT);

$PAGE->set_url('/local/dashboard_v3/detalle_sistema.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Sistema');
$PAGE->set_heading('Sistema');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));
$PAGE->requires->js(new moodle_url('/local/dashboard_v3/js/apexcharts.min.js'), true);

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_sistema', 'init', [
    'days' => $days
]);

$data = kpi_service_system::get_system_kpis($days);

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
echo $renderer->sidebar('sistema');
echo '</div>';

echo $OUTPUT->render_from_template('local_dashboard_v3/detalle_sistema', [
    'kpis' => $data['kpis'],
    'insights' => $data['insights'],
    'days' => $days,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo '</div>';

echo $OUTPUT->footer();