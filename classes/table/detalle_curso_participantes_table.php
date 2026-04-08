<?php

namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_curso_participantes_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'fullname',
            'email',
            'progress',
            'grade',
            'datecomplete',
            'year',
            'month'
        ]);

        $this->define_headers([
            'Nombre',
            'Correo',
            'Progreso',
            'Calificación',
            'Fecha fin',
            'Año',
            'Mes'
        ]);

        $this->pageable(true);
    }

    public function col_fullname($values) {
        return format_string($values->fullname);
    }
}