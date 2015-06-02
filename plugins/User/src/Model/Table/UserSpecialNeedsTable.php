<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserSpecialNeedsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('SpecialNeedTypes', ['className' => 'User.SpecialNeedTypes']);
	}

	public function beforeAction() {
		$this->fields['special_need_type_id']['type'] = 'select';
		$this->fields['special_need_type_id']['options'] = $this->SpecialNeedTypes->getList();
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
