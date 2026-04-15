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

            // Activos
            'isindex' => $currentpage === 'index',
            'isusuarios' => $currentpage === 'usuarios',
            'iscursos' => $currentpage === 'cursos',
    
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
}