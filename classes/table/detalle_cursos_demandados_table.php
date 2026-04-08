<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_cursos_demandados_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'categoria',
            'nombre',
            'usuarios_inscritos',
            'fecha_inicio',
            'fecha_fin',
            'acciones'
        ]);

        $this->define_headers([
            'Categoría',
            'Nombre del curso',
            'Usuarios inscritos',
            'Fecha inicio',
            'Fecha fin',
            'Acciones'
        ]);

        $this->sortable(true, 'usuarios_inscritos', SORT_DESC);
        $this->pageable(true);
    }

    public function col_nombre($values) {
        return format_string($values->nombre);
    }

    public function col_fecha_inicio($values) {
        return $values->fecha_inicio ? userdate($values->fecha_inicio, '%Y-%m-%d') : '-';
    }

    public function col_fecha_fin($values) {
        return $values->fecha_fin ? userdate($values->fecha_fin, '%Y-%m-%d') : '-';
    }

    public function col_acciones($values) {
        $url = new \moodle_url('/local/dashboard_v3/detalle_cursos_demandados.php', [
            'courseid' => $values->id
        ]);

        return '<a href="'.$url.'" class="btn btn-sm btn-primary">
                    Ver detalle
                </a>';
    }
}