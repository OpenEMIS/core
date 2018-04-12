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

		$this->belongsToMany('StaffPositionTitles', [
			'className' => 'Institution.StaffPositionTitles',
			'joinTable' => 'staff_position_titles_grades',
			'foreignKey' => 'staff_position_grade_id',  
			'targetForeignKey' => 'staff_position_title_id', 
			'through' => 'Institution.StaffPositionTitlesGrades',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
