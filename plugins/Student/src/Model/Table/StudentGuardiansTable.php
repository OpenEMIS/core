<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentGuardiansTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('GuardianUsers', ['className' => 'User.Users', 'foreignKey' => 'guardian_id']);
		$this->belongsTo('GuardianRelations', ['className' => 'FieldOption.GuardianRelations']);
		$this->belongsTo('GuardianEducationLevels', ['className' => 'FieldOption.GuardianEducationLevels']);
	}

	

	// public function beforeAction() {
	// 	$visibility = ['view' => true, 'edit' => true];
		
	// 	// $this->fields['file_type']['visible'] = ['add' => false, 'edit' => false];
	// 	// pr($this->action);
	// 	$this->fields['file_name']['visible'] = ['index' => true, 'view' => true];
	// 	if ($this->action == 'add' || $this->action == 'edit') {
	// 		$this->fields['file_name']['type'] = 'hidden';
	// 	}
		
	// 	if ($this->action == 'view') {
	// 		$session = $this->request->session();
	// 		$id = $session->check('GuardianAttachments.id') ? $session->read('GuardianAttachments.id') : false ;
	// 		if ($id) {
	// 			$this->fields['name']['type'] = 'download';
	// 			$this->fields['name']['attr']['url'] = array(
	// 				'plugin' => 'Institution',
	// 				'controller' => $this->controller->name,
	// 				'action' => 'Attachments',
	// 				'download',
	// 				$id
	// 			);

	// 			$this->fields['file_name']['visible'] = false;
	// 			$this->fields['file_content']['visible'] = true;
	// 			// $this->fields['file_content']['type']

	// 		} else {
	// 			$this->controller->redirect(array(
	// 				'plugin' => 'Institution',
	// 				'controller' => $this->controller->name,
	// 				'action' => 'Attachments'
	// 			));
	// 		}
	// 	} else if ($this->action == 'edit') {
	// 		$this->fields['file_content']['visible'] = false;
	// 		// $this->fields['file']['visible'] = ['index' => false, 'add' => true, 'edit' => false];
	// 	}


	// 	$this->fields['modified_user_id']['visible'] = ['index' => false, 'view' => true];
	// 	$this->fields['modified']['visible'] = ['index' => false, 'view' => true];
	// 	$this->fields['created_user_id']['visible'] = ['index' => false, 'view' => true];
		
	// }

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}
