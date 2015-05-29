<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('ModifiedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'created_user_id']);
	}

	public function beforeAction() {
		$this->fields['photo_content']['type'] = 'image';
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('username')
			->notEmpty('first_name');

		return $validator;
	}
}