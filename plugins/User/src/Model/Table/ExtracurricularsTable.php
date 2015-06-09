<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_extracurriculars');
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}