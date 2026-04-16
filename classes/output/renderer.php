<?php
namespace local_dashboard_v3\output;

use plugin_renderer_base;
use moodle_url;

class renderer extends plugin_renderer_base {

    public function sidebar($currentpage) {

        $data = [
            // URLs
            'indexurl' => (new moodle_url('/local/dashboard_v3/index.php'))->out(),
            'usuariosurl' => (new moodle_url('/local/dashboard_v3/detalle_usuarios.php'))->out(),
            'cursosurl' => (new moodle_url('/local/dashboard_v3/detalle_cursos.php'))->out(),
            'docentesurl' => (new moodle_url('/local/dashboard_v3/detalle_docentes.php'))->out(),

            // Activos
            'isindex' => $currentpage === 'index',
            'isusuarios' => $currentpage === 'usuarios',
            'iscursos' => $currentpage === 'cursos',
            'isdocentes' => $currentpage === 'docentes',
    
        ];

        return $this->render_from_template('local_dashboard_v3/sidebar', $data);
    }

    public function render_top_users_table($users)
    {
        $table = new \html_table();

        $table->head = [
            'Usuario',
            'Accesos',
            'Última actividad',
            'Cursos activos'
        ];

        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($users as $u) {
            $row = new \html_table_row();

            $row->cells = [
                $u['fullname'],
                $u['accesses'],
                $u['lastaccess'],
                $u['courses']
            ];

            $table->data[] = $row;
        }

        return \html_writer::table($table);
    }

    public function render_course_ranking_table($courses)
    {
        $table = new \html_table();

        $table->head = [
            'Curso',
            'Usuarios activos',
            'Eventos',
            '% Finalización',
            'Promedio avance',
            'Tendencia'
        ];

        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($courses as $c) {

            $trend_label = $c['trend'] > 0 ? '▲' : ($c['trend'] < 0 ? '▼' : '●');

            $trend_class = $c['trend'] > 0
                ? 'text-success'
                : ($c['trend'] < 0 ? 'text-danger' : 'text-muted');

            $row = new \html_table_row();

            $row->cells = [
                $c['fullname'],
                $c['activeusers'],
                $c['events'],
                $c['completion'] . '%',
                $c['avg_progress'],
                "<span class='{$trend_class}'>{$c['trend']}% {$trend_label}</span>"
            ];

            $table->data[] = $row;
        }

        return \html_writer::table($table);
    }

    public function render_teacher_ranking_table($teachers)
    {
        $table = new \html_table();

        $table->head = [
            'Docente',
            'Actividades',
            'Cursos intervenidos',
            'Última actividad',
            'Promedio diario',
            'Tendencia'
        ];

        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($teachers as $t) {

            $trend_label = $t['trend'] > 0 ? '▲' : ($t['trend'] < 0 ? '▼' : '●');

            $trend_class = $t['trend'] > 0
                ? 'text-success'
                : ($t['trend'] < 0 ? 'text-danger' : 'text-muted');

            $row = new \html_table_row();

            $row->cells = [
                $t['fullname'],
                $t['events'],
                $t['courses'],
                $t['lastactivity'],
                $t['avg_daily'],
                "<span class='{$trend_class}'>{$t['trend']}% {$trend_label}</span>"
            ];

            $table->data[] = $row;
        }

        return \html_writer::table($table);
    }
}