<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service.php');

use local_dashboard_v3\service\kpi_service;

$days = optional_param('days', 30, PARAM_INT);

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/index.php');
$PAGE->set_pagelayout('report');

$PAGE->set_title('dashboard_v3');
$PAGE->set_heading('dashboard_v3');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));
$PAGE->requires->js(new moodle_url('/local/dashboard_v3/js/apexcharts.min.js'), true);

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/dashboard_v3', 'init', [
    'days' => $days
]);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_dashboard_v3');

$data = \local_dashboard_v3\service\kpi_service::get_kpis_initial($days);

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

echo $OUTPUT->render_from_template('local_dashboard_v3/dashboard_v3', [
    'kpis' => $data['kpis'],
    'insights' => $data['insights'],
    'days' => $days,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo '</div>';

echo $OUTPUT->footer(); 