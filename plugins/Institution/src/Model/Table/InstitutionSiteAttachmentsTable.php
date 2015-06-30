<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator
				->allowEmpty('file_content');
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('file_content', ['visible' => ['edit' => true], 'type' => 'binary']);
		$this->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'visible' => ['add' => true, 'edit' => true]]);

		$this->ControllerAction->field('modified_user_id', ['visible' => ['view' => true]]);
		$this->ControllerAction->field('modified', ['visible' => ['view' => true]]);
		$this->ControllerAction->field('created_user_id', ['visible' => ['view' => true]]);

		$this->ControllerAction->field('file_name', ['visible' => false]);

		$this->ControllerAction->field('created', [
			'visible' => ['index'=>true, 'view'=>true],
			'type' => 'datetime',
		]);

		$this->ControllerAction->field('name', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->ControllerAction->field('description', [
			'visible' => true,
			'type' => 'select'
		]);

		$this->ControllerAction->field('file_type', [
			'visible' => ['index'=>true],
			'type' => 'string'
		]);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}


/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
	
		$this->ControllerAction->setFieldOrder([
			'name', 'description', 'file_type', 'created'
		]);
		
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

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
		return $entity;
    }


/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function editBeforeAction($event) {
	
		// $this->fields['file_content']['visible'] = false;
		unset($this->fields['file_content']);
		
    }

    public function editBeforePatch($event, $entity, $data, $options) {
		if (isset($data[$this->aliasField('file_content')])) {
			unset($data[$this->aliasField('file_content')]);
		}

		return compact('entity', 'data', 'options');
    }


/******************************************************************************************************************
**
** add action logics
**
******************************************************************************************************************/
	// public function addBeforePatch($event, $entity, $data, $options) {

 // 		return compact('entity', 'data', 'options');
 //    }


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetFileType(Event $event, Entity $entity) {
		return $this->getFileTypeForView($entity->file_name);
	}

}
