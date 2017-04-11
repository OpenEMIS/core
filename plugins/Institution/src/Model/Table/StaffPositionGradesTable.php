<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class StaffPositionGradesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_position_grades');
        parent::initialize($config);
        $this->hasMany('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_grade_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
