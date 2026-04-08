<?php

use local_dashboard_v3\table\detalle_cursos_demandados_table;
use local_dashboard_v3\table\detalle_curso_participantes_table;

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/dashboard_v3:view', $context);

$PAGE->set_url('/local/dashboard_v3/detalle_cursos_demandados.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('Cursos Demandados');
$PAGE->set_heading('Cursos Demandados');

$PAGE->requires->css(new moodle_url('/local/dashboard_v3/styles.css'));

$PAGE->requires->js_call_amd('local_dashboard_v3/sidebar', 'init');
$PAGE->requires->js_call_amd('local_dashboard_v3/detalle_cursos_categorias', 'init');

//  TABLA MOODLE
$table = new detalle_cursos_demandados_table('cursosdemandados');

$download = optional_param('download', '', PARAM_ALPHA);

$table->is_downloading($download, 'reporte_finalizacion_cursos', 'Finalización de Cursos');

// SQL
$table->set_sql(
    'id, categoria, nombre, usuarios_inscritos, fecha_inicio, fecha_fin',
    "(
        SELECT 
            c.id,
            cc.name AS categoria,
            c.fullname AS nombre,
            COUNT(ue.id) AS usuarios_inscritos,
            c.startdate AS fecha_inicio,
            c.enddate AS fecha_fin
        FROM {course} c
        JOIN {course_categories} cc ON c.category = cc.id
        LEFT JOIN {enrol} e ON e.courseid = c.id
        LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE c.id <> ".SITEID."
        GROUP BY c.id, cc.name, c.fullname, c.startdate, c.enddate
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

echo '<h4>Cursos Demandados</h4>';

$table->set_attribute('class', 'generaltable table local-dashboard-table');
$table->out(10, false); // 10 registros por página

// ========================
// DETALLE DE CURSO
// ========================
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid) {

    echo '<hr>';
    echo '<h5>Detalle del Curso</h5>';

    $detailtable = new detalle_curso_participantes_table('detallecurso');

    $detailtable->set_sql(
        'fullname, email, progress, grade, datecomplete, year, month',
        "(
            SELECT 
                u.id,
                CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                u.email,
                0 AS progress,
                '-' AS grade,
                '-' AS datecomplete,
                '-' AS year,
                '-' AS month
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = $courseid
        ) t",
        '1=1'
    );

    $detailtable->set_attribute('class', 'generaltable table local-dashboard-table');
    $detailtable->out(10, false);
}

echo '</div>'; // contenido

echo '</div>'; // layout

echo '</div>';

echo $OUTPUT->footer(); 