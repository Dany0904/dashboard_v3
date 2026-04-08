<?php
namespace local_dashboard_v3\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class detalle_vs_table extends \table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Columnas
        $this->define_columns([
            'category',
            'fullname',
            'participants',
            'completed',
            'notcompleted'
        ]);

        $this->define_columns([
            'id',
            'category',
            'fullname',
            'participants',
            'completed',
            'notcompleted'
        ]);

        // Config
        $this->column_class('id', 'd-none');
        $this->sortable(true, 'category', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_category($values) {
        return format_string($values->category);
    }

    public function col_fullname($values) {
        return format_string($values->fullname);
    }

    public function col_participants($values) {
        return '<div class="text-end">'.$values->participants.'</div>';
    }

    public function col_completed($values) {
        return '<div class="text-end text-success">'.$values->completed.'</div>';
    }

    public function col_notcompleted($values) {
        return '<div class="text-end text-danger">'.$values->notcompleted.'</div>';
    }
}