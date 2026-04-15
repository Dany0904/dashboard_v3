<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service_cursos.php');

use local_dashboard_v3\service\kpi_service_cursos;

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$days = optional_param('days', 30, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$courses = $DB->get_records_sql("
    SELECT id, fullname
    FROM {course}
    WHERE id <> :siteid
    ORDER BY fullname ASC
", ['siteid' => SITEID]);

$courselist = [];

foreach ($courses as $c) {
    $courselist[] = [
        'id' => $c->id,
        'name' => $c->fullname,
        'selected' => $c->id == $courseid
    ];
}

$PAGE->set_url('/local/dashboard_v3/detalle_cursos.php');
$PAGE->set_pagelayout('report');

$PAGE->set_title('Cursos');
$PAGE->set_heading('Cursos');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));
$PAGE->requires->js(new moodle_url('/local/dashboard_v3/js/apexcharts.min.js'), true);

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_cursos', 'init', [
    'days' => $days,
    'courseid' => $courseid
]);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_dashboard_v3');

$kpis = kpi_service_cursos::get_course_kpis($days, $courseid);

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
echo $renderer->sidebar('cursos');
echo '</div>';

echo $OUTPUT->render_from_template('local_dashboard_v3/detalle_cursos', [
    'kpis' => $kpis,
    'days' => $days,
    'courses' => $courselist,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo '</div>';

echo $OUTPUT->footer(); 