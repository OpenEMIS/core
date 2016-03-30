<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;

class EducationProgrammeOrientationsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'cascadeCallbacks' => true]);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
