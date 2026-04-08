<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_cursos_categorias_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns(['id', 'name', 'total', 'action']);
        $this->define_headers([
            'ID',
            'Categoría',
            'Total de Cursos',
            'Acción'
        ]);

        $this->sortable(true, 'total', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_name($values) {
        return format_string($values->name);
    }

    public function col_total($values) {
        return '<strong>'.$values->total.'</strong>';
    }

    public function col_action($values) {
        return '
            <button class="btn btn-sm btn-primary toggle-detalle-cat" 
                data-id="'.$values->id.'" 
                data-name="'.format_string($values->name).'">
                Ver cursos
            </button>
        ';
    }
}