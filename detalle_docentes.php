<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/dashboard_v3/classes/service/kpi_service_docentes.php');

use local_dashboard_v3\service\kpi_service_docentes;

require_login();

$context = context_system::instance();
require_capability('local/dashboard_v3:view', $context);

$days = optional_param('days', 30, PARAM_INT);

$PAGE->set_url('/local/dashboard_v3/detalle_docentes.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Docentes');
$PAGE->set_heading('Docentes');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$data = kpi_service_docentes::get_teacher_kpis($days);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_dashboard_v3/detalle_docentes', [
    'kpis' => $data['kpis'],
    'insights' => $data['insights'],
    'days' => $days,
    'is7' => $days == 7,
    'is30' => $days == 30,
    'is90' => $days == 90
]);

echo $OUTPUT->footer();