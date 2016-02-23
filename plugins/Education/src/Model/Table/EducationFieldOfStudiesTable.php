<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class EducationFieldOfStudiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->belongsTo('ProgrammeOrientations', ['className' => 'Education.EducationProgrammeOrientations', 'foreignKey' => 'education_programme_orientation_id']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'cascadeCallbacks' => true]);
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
