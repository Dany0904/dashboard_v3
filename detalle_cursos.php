<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service_cursos.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/course_table_service.php');

use local_dashboard_v3\service\kpi_service_cursos;
use local_dashboard_v3\service\course_table_service;

require_login();

$context = context_system::instance();
require_capability('local/dashboard_v3:view', $context);

$days = optional_param('days', 30, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// =========================
// COURSES LIST
// =========================
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

// =========================
// PAGE SETUP
// =========================
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

// =========================
// DATA
// =========================
$table_courses = course_table_service::get_courses_ranking($days);

$data = kpi_service_cursos::get_course_kpis($days, $courseid);

// =========================
// RENDER
// =========================
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
    'kpis' => $data['kpis'],
    'insights' => $data['insights'],
    'table_courses' => $renderer->render_course_ranking_table($table_courses),
    'courses' => $courselist,
    'days' => $days,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo '</div>';

echo $OUTPUT->footer();