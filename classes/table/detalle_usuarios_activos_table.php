<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_usuarios_activos_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Columnas
        $this->define_columns(['coursename', 'activeusers']);
        $this->define_headers([
            'Nombre del curso',
            'Usuarios activos (últimos 30 días)'
        ]);

        // Config
        $this->sortable(true, 'activeusers', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_coursename($values) {
        return format_string($values->coursename);
    }

    public function col_activeusers($values) {
        return $values->activeusers;
    }
}