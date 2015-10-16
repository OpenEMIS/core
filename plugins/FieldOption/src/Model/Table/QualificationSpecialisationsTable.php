<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class QualificationSpecialisationsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'qualification_specialisation_id']);
	}
}
