<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class InstitutionSiteAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all']);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('institution_site_id', 	['type' => 'hidden', 'visible' => ['edit' => true]]);

		$this->ControllerAction->field('modified', 				['visible' => ['view' => true]]);
		$this->ControllerAction->field('modified_user_id', 		['visible' => ['view' => true]]);
		$this->ControllerAction->field('created', 				['type' => 'datetime', 'visible' => ['index'=>true, 'view'=>true]]);
		$this->ControllerAction->field('created_user_id', 		['visible' => ['view' => true]]);

		$this->ControllerAction->field('file_name', 			['visible' => false]);
		$this->ControllerAction->field('file_content', 			['type' => 'binary', 'visible' => ['edit' => true]]);
		$this->ControllerAction->field('date_on_file', 			['type' => 'date', 'visible' => true]);

		$this->ControllerAction->field('name', 					['type' => 'string', 'visible' => true]);
		$this->ControllerAction->field('description', 			['type' => 'text', 'visible' => true]);

		$this->ControllerAction->field('file_type', 			['type' => 'string', 'visible' => ['index'=>true]]);
	}


/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event) {
	
		$this->ControllerAction->setFieldOrder([
			'name', 'description', 'file_type', 'date_on_file', 'created'
		]);
		
    }


/******************************************************************************************************************
**
** view action logics
**
******************************************************************************************************************/
    public function viewAfterAction(Event $event, Entity $entity) {
		$this->fields['name']['type'] = 'download';
		$this->fields['name']['attr']['url'] = $this->ControllerAction->buttons['download']['url'];
    	
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
    public function editBeforeAction(Event $event) {
		$this->fields['date_on_file']['visible'] = false;
    }


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetFileType(Event $event, Entity $entity) {
		return $this->getFileTypeForView($entity->file_name);
	}

}
