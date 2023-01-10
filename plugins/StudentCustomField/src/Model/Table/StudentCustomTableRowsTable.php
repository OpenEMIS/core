<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class StudentCustomTableRowsTable extends CustomTableRowsTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
        $this->hasMany('CustomTableCells', ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_custom_table_row_id', 'dependent' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'student_custom_field_id',
            ]);
        }
    }
}
