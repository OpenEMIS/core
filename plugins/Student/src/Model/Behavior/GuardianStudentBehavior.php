<?php
namespace Student\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Network\Request;
use Cake\Controller\Controller;

class GuardianStudentBehavior extends Behavior {
	private $associatedModel;
	public function initialize(array $config) {
		$this->associatedModel = (array_key_exists('associatedModel', $config))? $config['associatedModel']: null;
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$session = $this->_table->request->session();

		if ($session->check('Students.security_user_id')) {
			$student_user_id = $session->read('Students.security_user_id');
		} else {
			$student_user_id = 0;
		}
		$query
			->where(['student_user_id = '.$student_user_id])
			;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvents = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',

			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
			'ControllerAction.Model.add.afterSave' => 'addAfterSave',

			'ControllerAction.Model.onBeforeDelete' => 'onBeforeDelete',

			'ControllerAction.Model.onUpdateFieldGuardianRelationId' => 'onUpdateFieldGuardianRelationId',
			'ControllerAction.Model.onUpdateFieldGuardianEducationLevelId' => 'onUpdateFieldGuardianEducationLevelId',
			'Model.custom.onUpdateActionButtons' => 'onUpdateActionButtons',

		];

		$events = array_merge($events,$newEvents);
		return $events;
	}

	public function indexBeforeAction(Event $event) {
		// to set field order and other stuff
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $ids) {
		$process = function() use ($ids, $options) {
			// must also delete security roles here

			$entity = $this->associatedModel->get($ids);
			$guardianUserId = $entity->guardian_user_id;

			$remainingAssociatedCount = $this->associatedModel
				->find()
				->where([$this->associatedModel->aliasField('guardian_user_id') => $guardianUserId])
				->count();
			if ($remainingAssociatedCount<=1) {
				// need to reinsert associated array so that still recognise user as a 'student' or 'staff'
				$newAssociated = $this->associatedModel->newEntity(['guardian_user_id' => $guardianUserId]);
				$this->associatedModel->save($newAssociated);
			}

			return $this->associatedModel->delete($entity, $options->getArrayCopy());
		};
		return $process;
	}


	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		if ($this->_table->Session->check('Students.security_user_id')) {
			$studentSecurityUserId = $this->_table->Session->read('Students.security_user_id');

			$query->contain(['GuardianStudents' => function ($q) use ($studentSecurityUserId) {
				return $q->where(['GuardianStudents.student_user_id' => $studentSecurityUserId]);
			}]);
		}
	}


	public function addBeforeAction(Event $event) {
		if (array_key_exists('new', $this->_table->request->query)) {

		} else {
			foreach ($this->_table->fields as $key => $value) {
				$this->_table->fields[$key]['visible'] = false;
			}
			$session = $this->_table->request->session();
			$studentSecurityUserId = $session->read('Students.security_user_id');
			// pr($studentSecurityUserId);
			$associationString = $this->_table->alias().'.'.Inflector::tableize($this->associatedModel->alias()).'.0.';
			$this->_table->ControllerAction->field('student_user_id', ['type' => 'hidden', 'value' => $studentSecurityUserId, 'fieldName' => $associationString.'student_user_id']);

			$this->_table->ControllerAction->field('guardian_relation_id', ['fieldName' => $associationString.'guardian_relation_id']);
			$this->_table->ControllerAction->field('guardian_education_level_id', ['fieldName' => $associationString.'guardian_education_level_id']);
			$this->_table->ControllerAction->field('search',['type' => 'autocomplete',
														     'placeholder' => 'OpenEMIS ID, Identity Number or Name',
														     'url' => '/Students/Guardians/autoCompleteUserList',
														     'length' => 3 ]);

			$this->_table->ControllerAction->setFieldOrder([
					'guardian_relation_id', 'guardian_education_level_id'
				, 'search'
				]);
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
					return $this->_table->controller->redirect(['plugin' => 'Student', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'add', 'new' => $timeNow]);
				} else {
					$data[$this->_table->alias()]['guardian_students'][0]['guardian_user_id'] = $currSearch;
					if ($this->associatedModel->save($this->associatedModel->newEntity($data[$this->_table->alias()]['guardian_students'][0]))) {
						$this->_table->ControllerAction->Alert->success('general.add.success');
					} else {
						$this->_table->ControllerAction->Alert->error('general.add.failed');
					}
					$event->stopPropagation();
					return $this->_table->controller->redirect(['plugin' => 'Student', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'index']);
				}
			}
		}
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		// that function removes the session and makes it redirect to
		// index without any named params
		// else the 'new' url param will cause it to add it with previous settings (from institution site student / staff)
		$action = $this->_table->ControllerAction->buttons['index']['url'];
		if (array_key_exists('new', $action)) {
			$session = $this->controller->request->session();
			$sessionVar = $this->_table->alias().'.add';
			// $session->delete($sessionVar); // removeed... should be placed somewhere like index
			unset($action['new']);
		}
		$event->stopPropagation();
		return $this->controller->redirect($action);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		if (isset($entity->guardian_students)) {
			if (array_key_exists(0, $entity->guardian_students)) {
				$guardianId = $entity->guardian_students[0]->guardian_user_id;

				if (array_key_exists('view', $buttons)) {
					$buttons['view']['url'][1] = $this->_table->ControllerAction->paramsEncode(['id' => $guardianId]);
				}
				if (array_key_exists('edit', $buttons)) {
					$buttons['edit']['url'][1] = $this->_table->ControllerAction->paramsEncode(['id' => $guardianId]);
				}
				if (array_key_exists('remove', $buttons)) {
					$buttons['remove']['attr']['field-value'] = $this->_table->ControllerAction->paramsEncode(['id' => $entity->guardian_students[0]->id]);
				}
			}
		}

		// because this is a behavior, it will call appTable's onUpdateActionButtons again
		$event->stopPropagation();
		return $buttons;
	}

	public function onUpdateFieldGuardianRelationId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->StudentGuardians->GuardianRelations->getList()->toArray();
		if (empty($attr['options'])){
			$this->_table->ControllerAction->Alert->warning('Institution.StudentGuardians.guardianRelationId');
		}
		return $attr;
	}

	public function onUpdateFieldGuardianEducationLevelId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->StudentGuardians->GuardianEducationLevels->getList()->toArray();
		if (empty($attr['options'])){
			$this->_table->ControllerAction->Alert->warning('Institution.StudentGuardians.guardianEducationLevel');
		}
		return $attr;
	}


}
