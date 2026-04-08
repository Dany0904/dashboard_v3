<?php

use local_dashboard_v3\table\detalle_cursos_categorias_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_cursos_categorias.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Cursos por Categoría');
$PAGE->set_heading('Cursos por Categoría');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_cursos_categorias', 'init');

//  TABLA MOODLE
$table = new detalle_cursos_categorias_table('cursoscategoria');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'reporte_finalizacion_cursos', 'Finalización de Cursos');

// SQL
$table->set_sql(
    'id, name, total',
    "(
        SELECT 
            c.id,
            c.name,
            COUNT(co.id) AS total
        FROM {course_categories} c
        LEFT JOIN {course} co 
            ON co.category = c.id
           AND co.id <> ".SITEID."
           AND co.timecreated > 0
        GROUP BY c.id, c.name
        ORDER BY total DESC
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

echo '<h4>Cursos por Categoría</h4>';

$table->set_attribute('class', 'generaltable table local-dashboard-table');
$table->out(10, false); // 10 registros por página

echo '</div>'; // contenido

echo '</div>'; // layout

echo '</div>';

echo $OUTPUT->footer(); 