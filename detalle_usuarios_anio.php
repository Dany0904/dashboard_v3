<?php

use local_dashboard_v3\table\detalle_usuarios_anio_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_usuarios_anio.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Detalle de Usuarios');
$PAGE->set_heading('Detalle de Usuarios');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');

//  TABLA MOODLE
$table = new detalle_usuarios_anio_table('detalleusuariosanio');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'usuarios_por_anio', 'Usuarios por Año');

// SQL
$sql = "
    SELECT
        YEAR(FROM_UNIXTIME(u.timecreated)) AS year,
        COUNT(1) AS total
    FROM {user} u
    WHERE u.timecreated > 0
      AND u.deleted = 0
    GROUP BY YEAR(FROM_UNIXTIME(u.timecreated))
";

// Total registros
$countsql = "
    SELECT COUNT(DISTINCT YEAR(FROM_UNIXTIME(u.timecreated)))
    FROM {user} u
    WHERE u.timecreated > 0
      AND u.deleted = 0
";

// Configurar tabla
$table->set_sql(
    'year, total',
    '(SELECT YEAR(FROM_UNIXTIME(u.timecreated)) AS year,
             COUNT(1) AS total
        FROM {user} u
       WHERE u.timecreated > 0
         AND u.deleted = 0
    GROUP BY YEAR(FROM_UNIXTIME(u.timecreated))) t',
    '1=1'
);

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

echo '<h4>Detalle de Usuarios por Año</h4>';

$table->set_attribute('class', 'generaltable table local-dashboard-table');
$table->out(10, false); // 10 registros por página

echo '</div>'; // contenido

echo '</div>'; // layout

echo '</div>';

echo $OUTPUT->footer(); 