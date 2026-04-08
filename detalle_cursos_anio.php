<?php

use local_dashboard_v3\table\detalle_cursos_anio_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_vs.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Cursos por Año');
$PAGE->set_heading('Cursos por Año');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_cursos_anio', 'init');

//  TABLA MOODLE
$table = new detalle_cursos_anio_table('cursosanio');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'reporte_finalizacion_cursos', 'Finalización de Cursos');

$table->set_sql(
    'year, total',
    "(SELECT 
        YEAR(FROM_UNIXTIME(c.timecreated)) AS year,
        COUNT(1) AS total
     FROM {course} c
     WHERE c.id <> ".SITEID."
       AND c.timecreated > 0
     GROUP BY YEAR(FROM_UNIXTIME(c.timecreated))
    ) t",
    '1=1'
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