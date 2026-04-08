<?php

use local_dashboard_v3\table\detalle_usuarios_activos_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_usuarios_activos.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Usuarios activos en cursos');
$PAGE->set_heading('Usuarios activos en cursos');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');

//  TABLA MOODLE
$table = new detalle_usuarios_activos_table('usuariosactivos');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'usuarios_activos_cursos', 'Usuarios activos en cursos');

//  RANGO ÚLTIMOS 30 DÍAS
$year  = (int)date('Y');
$start = strtotime($year . '-01-01 00:00:00');
$end   = strtotime(($year + 1) . '-01-01 00:00:00');

//  QUERY (SUBQUERY para table_sql)
$table->set_sql(
    'coursename, activeusers',
    "(SELECT
        c.fullname AS coursename,
        COUNT(DISTINCT l.userid) AS activeusers
      FROM {logstore_standard_log} l
      JOIN {course} c ON c.id = l.courseid
     WHERE l.timecreated >= $start
       AND l.timecreated < $end
       AND l.courseid <> " . SITEID . "
       AND l.userid <> 1
       AND c.category > 0
     GROUP BY c.id, c.fullname
     ORDER BY activeusers DESC
     LIMIT 10) t",
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

echo '<h4>Detalle de Usuarios Activos en Cursos</h4>';

$table->set_attribute('class', 'generaltable table local-dashboard-table');
$table->out(10, false); // 10 registros por página

echo '</div>'; // contenido

echo '</div>'; // layout

echo '</div>';

echo $OUTPUT->footer(); 