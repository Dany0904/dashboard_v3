<?php
namespace local_dashboard_v3\output;

use plugin_renderer_base;
use moodle_url;

class renderer extends plugin_renderer_base {

    public function sidebar($currentpage) {

        $data = [
            // URLs
            'indexurl' => (new moodle_url('/local/dashboard_v3/index.php'))->out(),
            'preturnosurl' => (new moodle_url('/local/dashboard_v3/detalle_preturnos.php'))->out(),
            'quizurl' => (new moodle_url('/local/dashboard_v3/detalle_quiz.php'))->out(),
            'horasurl' => (new moodle_url('/local/dashboard_v3/detalle_horas.php'))->out(),

            'usuariosaniourl' => (new moodle_url('/local/dashboard_v3/detalle_usuarios_anio.php'))->out(),
            'usuariosactivosurl' => (new moodle_url('/local/dashboard_v3/detalle_usuarios_activos.php'))->out(),
            'vsurl' => (new moodle_url('/local/dashboard_v3/detalle_vs.php'))->out(),

            'cursosaniourl' => (new moodle_url('/local/dashboard_v3/detalle_cursos_anio.php'))->out(),
            'cursoscaturl' => (new moodle_url('/local/dashboard_v3/detalle_cursos_categorias.php'))->out(),
            'cursosdemurl' => (new moodle_url('/local/dashboard_v3/detalle_cursos_demandados.php'))->out(),
            'cursospromurl' => (new moodle_url('/local/dashboard_v3/detalle_cursos_promedio.php'))->out(),
            'cursosprogurl' => (new moodle_url('/local/dashboard_v3/detalle_cursos_progreso.php'))->out(),

            'calificacionesurl' => (new moodle_url('/local/dashboard_v3/detalle_calificaciones.php'))->out(),
            'progresourl' => (new moodle_url('/local/dashboard_v3/detalle_progreso_by_user.php'))->out(),

            // Activos
            'isindex' => $currentpage === 'index',
            'ispreturnos' => $currentpage === 'preturnos',
            'isquiz' => $currentpage === 'quiz',
            'ishoras' => $currentpage === 'horas',

            'isusuariosanio' => $currentpage === 'usuarios_anio',
            'isusuariosactivos' => $currentpage === 'usuarios_activos',
            'isvs' => $currentpage === 'vs',

            'iscursosanio' => $currentpage === 'cursos_anio',
            'iscursoscat' => $currentpage === 'cursos_cat',
            'iscursosdem' => $currentpage === 'cursos_dem',
            'iscursosprom' => $currentpage === 'cursos_prom',
            'iscursosprog' => $currentpage === 'cursos_prog',

            'iscalificaciones' => $currentpage === 'calificaciones',
            'isprogreso' => $currentpage === 'progreso',
        ];

        // Abrir menús automáticamente
        $data['usuarios_open'] = (
            $data['isusuariosanio'] ||
            $data['isusuariosactivos'] ||
            $data['isvs']
        );

        $data['cursos_open'] = (
            $data['iscursosanio'] ||
            $data['iscursoscat'] ||
            $data['iscursosdem'] ||
            $data['iscursosprom'] ||
            $data['iscursosprog']
        );

        return $this->render_from_template('local_dashboard_v3/sidebar', $data);
    }
}