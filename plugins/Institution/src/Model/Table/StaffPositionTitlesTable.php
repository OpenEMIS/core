<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffPositionTitlesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
        $this->hasMany('Titles', ['className' => 'Institution.InstitutionSitePositions', 'foreignKey' => 'staff_position_title_id']);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id']);
	}
}
