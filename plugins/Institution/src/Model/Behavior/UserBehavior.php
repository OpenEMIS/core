<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Controller\Controller;

class UserBehavior extends Behavior {
	private $associatedModel;
	public function initialize(array $config) {
		$this->associatedModel = (array_key_exists('associatedModel', $config))? $config['associatedModel']: null;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvents = [
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
			'ControllerAction.Model.add.afterSave' => 'addAfterSave',
		];

		$events = array_merge($events,$newEvents);
		return $events;
	}

	// public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
	// 	$process = function() use ($id, $options) {
	// 		// must also delete security roles here

	// 		$entity = $this->associatedModel->get($id);
	// 		$securityUserId = $entity->security_user_id;

	// 		$remainingAssociatedCount = $this->associatedModel
	// 			->find()
	// 			->where([$this->associatedModel->aliasField('security_user_id') => $securityUserId])
	// 			->count();
	// 		if ($remainingAssociatedCount<=1) {
	// 			// need to reinsert associated array so that still recognise user as a 'student' or 'staff'
	// 			$newAssociated = $this->associatedModel->newEntity(['security_user_id' => $securityUserId]);
	// 			$this->associatedModel->save($newAssociated);
	// 		}

	// 		// must also delete security roles here if it is a staff
	// 		if ($this->_table->hasBehavior('Staff')) {
	// 			$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

	// 			if ($this->_table->Session->check('Institutions.id')) {
	// 				$institutionId = $this->_table->Session->read('Institutions.id');
	// 			}


	// 			// if got security_user and institution then delete all
	// 			if (isset($institutionId) && isset($securityUserId)) {
	// 				$Institution = TableRegistry::get('Institution.Institutions');
	// 				$institutionData = $Institution->get($institutionId);
					
	// 				$securityGroupId = $institutionData->security_group_id;


	// 				$conditions = [
	// 								'security_group_id' => $securityGroupId,
	// 								'security_user_id' => $securityUserId
	// 								];	
	// 				$SecurityGroupUsers->deleteAll($conditions);
	// 			}
				

	// 		}
			
	// 		return $this->associatedModel->delete($entity, $options->getArrayCopy());
	// 	};
	// 	return $process;
	// }

	public function addBeforeAction(Event $event) {
		if (array_key_exists('new', $this->_table->request->query)) {

		} else {
			foreach ($this->_table->fields as $key => $value) {
				$this->_table->fields[$key]['visible'] = false;
			}
			$session = $this->_table->request->session();
			$institutionSiteId = $session->read('Institutions.id');
			$associationString = $this->_table->alias().'.'.$this->associatedModel->table().'.0.';
			$this->_table->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionSiteId, 'fieldName' => $associationString.'institution_site_id']);
		}
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (!array_key_exists('new', $this->_table->request->query)) {
			$newOptions = [];
			$newOptions['validate'] = false;

			$arrayOptions = $options->getArrayCopy();
			$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
			$options->exchangeArray($arrayOptions);
		}
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (!array_key_exists('new', $this->_table->request->query)) {
			$timeNow = strtotime("now");
			$sessionVar = $this->_table->alias().'.add.'.strtotime("now");
			$session = $this->_table->request->session();
			$session->write($sessionVar, $this->_table->request->data);

			$currSearch = null;
			if (array_key_exists('search', $data[$this->_table->alias()])) {
				$currSearch = $data[$this->_table->alias()]['search'];
				unset($data[$this->_table->alias()]['search']);
			}

			if (!$entity->errors()) {
				if (!$currSearch) {
					$event->stopPropagation();
					return $this->_table->controller->redirect(['plugin' => 'Institution', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'add', 'new' => $timeNow]);
				} else {
					$data[$this->_table->alias()][$this->associatedModel->table()][0]['security_user_id'] = $currSearch;


					$newEntity = $this->associatedModel->newEntity($data[$this->_table->alias()][$this->associatedModel->table()][0]);
					if ($this->associatedModel->save($newEntity)) {

						// need to insert section over here because it will redirect before going to aftersave
						if ($this->_table->hasBehavior('Student')) {
							$sectionData['student_id'] = $newEntity->security_user_id;
							$sectionData['education_grade_id'] = $entity->institution_site_students[0]['education_grade'];
							$sectionData['institution_site_section_id'] = $entity->institution_site_students[0]['section'];

							$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');

							$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);	
						}

						// need to insert security roles here because it will redirect before going to aftersave
						if ($this->_table->hasBehavior('Staff')) {
							TableRegistry::get('Security.SecurityGroupUsers')->insertSecurityRoleForInstitution($data[$this->_table->alias()][$this->associatedModel->table()][0]);
						}

						$this->_table->ControllerAction->Alert->success('general.add.success');
					} else {
						$this->_table->ControllerAction->Alert->error('general.add.failed');
					}
					$event->stopPropagation();
					return $this->_table->controller->redirect(['plugin' => 'Institution', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'index']);
				}

			}
		}
	}

	public function addAfterSave(Event $event, Controller $controller, Entity $entity) {
		if ($this->_table->hasBehavior('Staff')) {
			// need to insert security roles here
			$data = $this->_table->ControllerAction->request->data[$this->_table->alias()][$this->associatedModel->table()][0];
			$data['security_user_id'] = $entity->id;
			TableRegistry::get('Security.SecurityGroupUsers')->insertSecurityRoleForInstitution($data);
		}

		// that function removes the session and makes it redirect to 
		// index without any named params
		// else the 'new' url param will cause it to add it with previous settings (from institution site student / staff)
		$action = $this->_table->ControllerAction->buttons['index']['url'];
		if (array_key_exists('new', $action)) {
			$session = $controller->request->session();
			$sessionVar = $this->_table->alias().'.add';
			// $session->delete($sessionVar); // removeed... should be placed somewhere like index
			unset($action['new']);
		}
		$event->stopPropagation();
		return $controller->redirect($action);
	}

}
