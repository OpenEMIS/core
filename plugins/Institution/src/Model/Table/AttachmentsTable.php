<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AttachmentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_attachments');
		parent::initialize($config);
		
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		$visibility = ['view' => true, 'edit' => true];
		
		// $this->fields['file_type']['visible'] = ['add' => false, 'edit' => false];
		// pr($this->action);
		$this->fields['file_name']['visible'] = ['index' => true, 'view' => true];
		if ($this->action == 'add' || $this->action == 'edit') {
			$this->fields['file_name']['type'] = 'hidden';
		}
		
		if ($this->action == 'view') {
			$session = $this->request->session();
			$id = $session->check('Attachments.id') ? $session->read('Attachments.id') : false ;
			if ($id) {
				$this->fields['name']['type'] = 'download';
				$this->fields['name']['attr']['url'] = array(
					'plugin' => 'Institution',
					'controller' => $this->controller->name,
					'action' => 'Attachments',
					'download',
					$id
				);

				$this->fields['file_name']['visible'] = false;
				$this->fields['file_content']['visible'] = true;
				// $this->fields['file_content']['type']

			} else {
				$this->controller->redirect(array(
					'plugin' => 'Institution',
					'controller' => $this->controller->name,
					'action' => 'Attachments'
				));
			}
		} else if ($this->action == 'edit') {
			$this->fields['file_content']['visible'] = false;
			// $this->fields['file']['visible'] = ['index' => false, 'add' => true, 'edit' => false];
		}


		$this->fields['modified_user_id']['visible'] = ['index' => false, 'view' => true];
		$this->fields['modified']['visible'] = ['index' => false, 'view' => true];
		$this->fields['created_user_id']['visible'] = ['index' => false, 'view' => true];
		
	}
}
