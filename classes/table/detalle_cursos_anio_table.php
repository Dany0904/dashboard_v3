<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_cursos_anio_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns(['year', 'total', 'action']);
        $this->define_headers([
            'Año',
            'Total de Cursos',
            'Acción'
        ]);

        $this->sortable(true, 'year', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_year($values) {
        return $values->year;
    }

    public function col_total($values) {
        return '<strong>'.$values->total.'</strong>';
    }

    public function col_action($values) {
        return '
            <button class="btn btn-sm btn-primary toggle-detalle" data-year="'.$values->year.'">
                Ver cursos
            </button>
        ';
    }
}