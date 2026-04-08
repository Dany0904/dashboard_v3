<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_usuarios_anio_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Columnas
        $this->define_columns(['year', 'total']);
        $this->define_headers(['Año', 'Total']);

        // Configuración
        $this->set_attribute('class', 'generaltable');
        $this->sortable(true, 'year', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_year($values) {
        return $values->year;
    }

    public function col_total($values) {
        return $values->total;
    }
}