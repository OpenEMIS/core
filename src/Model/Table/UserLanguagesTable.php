<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserLanguagesTable extends AppTable {
	public $validator;

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function beforeAction() {
		$gradeOptions = $this->getGradeOptions();
		$this->fields['listening']['type'] = 'select';
		$this->fields['listening']['options'] = $gradeOptions;
		$this->fields['speaking']['type'] = 'select';
		$this->fields['speaking']['options'] = $gradeOptions;
		$this->fields['reading']['type'] = 'select';
		$this->fields['reading']['options'] = $gradeOptions;
		$this->fields['writing']['type'] = 'select';
		$this->fields['writing']['options'] = $gradeOptions;
	}

	public function getGradeOptions() {
		$gradeOptions = array();
		for ($i = 0; $i < 6; $i++) {
			$gradeOptions[$i] = $i;
		}
		return $gradeOptions;
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

}
