<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserSpecialNeedsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityUsers', ['className' => 'SecurityUsers']);
		$this->belongsTo('SpecialNeedTypes', ['className' => 'SpecialNeedTypes']);
		$this->belongsTo('ModifiedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'created_user_id']);
	}

	public function beforeAction() {
		$this->fields['special_need_type_id']['type'] = 'select';
		$this->fields['special_need_type_id']['options'] = $this->SpecialNeedTypes->getList();
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
