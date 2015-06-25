<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}


/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
	
		$this->fields['file_content']['visible']['index'] = false;
		$this->fields['created']['visible']['index'] = true;
		
    }


/******************************************************************************************************************
**
** view action logics
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {
	
		$this->fields['file_name']['visible']['view'] = false;
		$this->fields['file_content']['visible']['view'] = false;
		
		$session = $this->request->session();
		$primaryKey = $this->primaryKey();
		$idKey = $this->aliasField($primaryKey);
		$id = $session->check($idKey) ? $session->read($idKey) : false ;
		if ($id) {
			$this->fields['name']['type'] = 'download';
			$this->fields['name']['attr']['url'] = array(
				'plugin' => 'Institution',
				'controller' => $this->controller->name,
				'action' => 'Attachments',
				'download',
				$id
			);

			// $this->fields['file_content']['visible'] = true;
			// $this->fields['file_content']['type']

		} else {
			$this->controller->redirect(array(
				'plugin' => 'Institution',
				'controller' => $this->controller->name,
				'action' => 'Attachments'
			));
		}

    }


/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function editBeforeAction($event) {
	
		$this->fields['file_name']['type'] = 'hidden';
		$this->fields['file_content']['visible'] = false;
		
    }


/******************************************************************************************************************
**
** add action logics
**
******************************************************************************************************************/
    public function addBeforeAction($event) {
	
		$this->fields['file_name']['type'] = 'hidden';
						
	 //    $validator = $this->validator();
	 //    if ($validator->hasField('file_name')) {
		// 	$fileNameValidation = $validator->field('file_name');
		// 	$fileNameValidation->remove('notBlank');
		// }
    }

	public function addBeforePatch($event, $entity, $data, $options) {
		/**
		 * thed's method to call method in behavior
		 */
		// if ($this->behaviors()->hasMethod('addBeforePatch')) {
		// 	list($entity, $data, $options) = array_values($this->behaviors()->call('addBeforePatch', [$event, $entity, $data, $options]));
		// }

		/**
		 * hanafi's method to call method in behavior
		 */
		// $entity->file_content = $data['InstitutionSiteAttachments']['file_content'];
		// $entity = $this->customFunction($entity);
		

		/**
		 * common statements after calling method in behavior
		 */
		// $data['InstitutionSiteAttachments']['file_name'] = $entity->file_name;
		// $data['InstitutionSiteAttachments']['file_content'] = $entity->file_content;
 		
 		return compact('entity', 'data', 'options');

    }

}
