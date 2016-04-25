<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;

class EducationFieldOfStudiesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->belongsTo('ProgrammeOrientations', ['className' => 'Education.EducationProgrammeOrientations', 'foreignKey' => 'education_programme_orientation_id']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'cascadeCallbacks' => true]);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_programme_orientation_id',
			]);
		}
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['education_programme_orientation_id']['type'] = 'select';
	}
}
