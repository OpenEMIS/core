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

class StudentGuardianBehavior extends Behavior {
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
			'ControllerAction.Model.onUpdateFieldGuardianRelationId' => 'onUpdateFieldGuardianRelationId',
			'ControllerAction.Model.onUpdateFieldGuardianEducationLevelId' => 'onUpdateFieldGuardianEducationLevelId',
			'Model.custom.onUpdateActionButtons' => 'onUpdateActionButtons',
	// 		'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
	// 		'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
	// 		'ControllerAction.Model.add.afterSaveRedirect' => 'addAfterSaveRedirect',
		];

		$events = array_merge($events,$newEvents);
		return $events;
	}

	public function indexBeforeAction(Event $event) {
		// to set field order and other stuff
	}


	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		if ($this->_table->Session->check('Students.security_user_id')) {
			$studentSecurityUserId = $this->_table->Session->read('Students.security_user_id');
			$options['contain'] = [
				'GuardianStudents' => [
					'conditions' => [
						'GuardianStudents.student_user_id' => $studentSecurityUserId
					]
				]
			];
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
			$associationString = $this->_table->alias().'.'.$this->associatedModel->table().'.0.';
			$this->_table->ControllerAction->field('security_user_id', ['type' => 'hidden', 'value' => $studentSecurityUserId, 'fieldName' => $associationString.'security_user_id']);

			$this->_table->ControllerAction->field('guardian_relation_id', ['fieldName' => $associationString.'academic_period']);
			$this->_table->ControllerAction->field('guardian_education_level_id', ['fieldName' => $associationString.'education_programme_id']);
			$this->_table->ControllerAction->field('search',['type' => 'autocomplete', 
														     'placeholder' => 'openEMIS ID or Name',
														     'url' => '/Students/Guardians/autoCompleteUserList',
														     'length' => 3 ]);

			$this->_table->ControllerAction->setFieldOrder([
					'guardian_relation_id', 'guardian_education_level_id'
				, 'search'
				]);	
// 			Search
// Guardian Relation
// First Name
// Last Name
// Gender
// Mobile Phone
// Office Phone
// Email
// Address
// Postal Code
// Guardian Education Level
// Comments
		}
	}

	// public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
	// 	if (!array_key_exists('new', $this->_table->request->query)) {
	// 		$newOptions = [];
	// 		$newOptions['validate'] = false;

	// 		$arrayOptions = $options->getArrayCopy();
	// 		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
	// 		$options->exchangeArray($arrayOptions);
	// 	}
	// }

	// public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
	// 	if (!array_key_exists('new', $this->_table->request->query)) {
	// 		$timeNow = strtotime("now");
	// 		$sessionVar = $this->_table->alias().'.add.'.strtotime("now");
	// 		$session = $this->_table->request->session();
	// 		$session->write($sessionVar, $this->_table->request->data);

	// 		$currSearch = null;
	// 		if (array_key_exists('search', $data[$this->_table->alias()])) {
	// 			$currSearch = $data[$this->_table->alias()]['search'];
	// 			unset($data[$this->_table->alias()]['search']);
	// 		}

	// 		if (!$entity->errors()) {
	// 			if (!$currSearch) {
	// 				$event->stopPropagation();
	// 				return $this->_table->controller->redirect(['plugin' => 'Institution', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'add', 'new' => $timeNow]);
	// 			} else {
	// 				$data[$this->_table->alias()][$this->associatedModel->table()][0]['security_user_id'] = $currSearch;
	// 				if ($this->associatedModel->save($this->associatedModel->newEntity($data[$this->_table->alias()][$this->associatedModel->table()][0]))) {
	// 					$this->_table->ControllerAction->Alert->success('general.add.success');
	// 				} else {
	// 					$this->_table->ControllerAction->Alert->error('general.add.failed');
	// 				}
	// 				$event->stopPropagation();
	// 				return $this->_table->controller->redirect(['plugin' => 'Institution', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'index']);
	// 			}

	// 		}
	// 	}
	// }

	// public function addAfterSave(Event $event, Controller $controller) {
	// 	if (array_key_exists('new', $action)) {
	// 		$session = $controller->request->session();
	// 		$sessionVar = $this->_table->alias().'.add';
	// 		$session->delete($sessionVar);
	// 		unset($action['new']);
	// 	}
	// 	$event->stopPropagation();
	// 	return $controller->redirect($action);
	// }

	// public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
	// 	$session = $this->_table->request->session();
	// 	$studentSecurityUserId = $session->read('Students.security_user_id');
	// 	$conditions = array(
	// 		'InstitutionSiteProgrammes.security_user_id' => $studentSecurityUserId
	// 	);

	// 	$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
	// 	$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $list;
	// 	$attr['onChangeReload'] = 'true';

	// 	return $attr;
	// }

	// public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
	// 	$session = $this->_table->request->session();
	// 	$studentSecurityUserId = $session->read('Students.security_user_id');
	// 	$this->academicPeriodId = null;
	// 	if (array_key_exists('academic_period', $this->_table->fields)) {
	// 		if (array_key_exists('options', $this->_table->fields['academic_period'])) {
	// 			$this->academicPeriodId = key($this->_table->fields['academic_period']['options']);
	// 			if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
	// 				if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['academic_period']) {
	// 					$this->academicPeriodId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['academic_period'];
	// 				}
	// 			}

	// 		}
	// 	}
	// 	$attr['type'] = 'select';
	// 	$attr['onChangeReload'] = 'true';

	// 	if (isset($this->academicPeriodId)) {
	// 		$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
	// 		$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($studentSecurityUserId, $this->academicPeriodId);
	// 	}

	// 	return $attr;
	// }

	// public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
	// 	$session = $this->_table->request->session();
	// 	$studentSecurityUserId = $session->read('Students.security_user_id');

	// 	if (array_key_exists('education_programme_id', $this->_table->fields)) {
	// 		if (array_key_exists('options', $this->_table->fields['education_programme_id'])) {
	// 			$this->educationProgrammeId = key($this->_table->fields['education_programme_id']['options']);
	// 			if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
	// 				if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_programme_id']) {
	// 					$this->educationProgrammeId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_programme_id'];
	// 				}
	// 			}
	// 		}
	// 	}
	// 	$attr['type'] = 'select';
	// 	$attr['onChangeReload'] = 'true';

	// 	if (isset($this->educationProgrammeId)) {
	// 		$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
	// 		$attr['options'] = $InstitutionSiteGrades->getGradeOptions($studentSecurityUserId, $this->academicPeriodId, $this->educationProgrammeId);
	// 	}

	// 	return $attr;
	// }

	// public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
	// 	$session = $this->_table->request->session();
	// 	$studentSecurityUserId = $session->read('Students.security_user_id');

	// 	if (array_key_exists('education_grade', $this->_table->fields)) {
	// 		if (array_key_exists('options', $this->_table->fields['education_grade'])) {
	// 			$this->education_grade = key($this->_table->fields['education_grade']['options']);
	// 			if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
	// 				if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_grade']) {
	// 					$this->education_grade = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_grade'];
	// 				}
	// 			}
	// 		}
	// 	}
	// 	$attr['type'] = 'select';

	// 	if (isset($this->education_grade)) {
	// 		$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
	// 		$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $studentSecurityUserId, $this->education_grade);
	// 	}

	// 	return $attr;
	// }

	// public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $this->associatedModel->StudentStatuses->getList();

	// 	return $attr;
	// }

	// public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, $request) {
	// 	$session = $this->_table->request->session();
	// 	$studentSecurityUserId = $session->read('Students.security_user_id');

	// 	$InstitutionSitePositions = TableRegistry::get('Institution.InstitutionSitePositions');
	// 	$list = $InstitutionSitePositions->getInstitutionSitePositionList($studentSecurityUserId, true);

	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $list;
	// 	$attr['onChangeReload'] = 'true';

	// 	return $attr;
	// }

	// public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
	// 	$attr['onChangeReload'] = 'true';
	// 	return $attr;
	// }

	// public function onUpdateFieldFTE(Event $event, array $attr, $action, $request) {
	// 	if (array_key_exists('institution_site_position_id', $this->_table->fields)) {
	// 		if (array_key_exists('options', $this->_table->fields['institution_site_position_id'])) {
	// 			$positionId = key($this->_table->fields['institution_site_position_id']['options']);
	// 			if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
	// 				if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['institution_site_position_id']) {
	// 					$positionId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['institution_site_position_id'];
	// 				}
	// 			}
	// 		}
	// 	}

	// 	$startDate = null;
	// 	if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
	// 		if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['start_date']) {
	// 			$startDate = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['start_date'];
	// 		}
	// 	}

	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $this->getFTEOptions($positionId, ['startDate' => $startDate]);
	// 	return $attr;
	// }

	// public function getFTEOptions($positionId, $options = []) {
	// 	$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
	// 	$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
	// 	$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
	// 	$options['startDate'] = !empty($options['startDate']) ? date('Y-m-d', strtotime($options['startDate'])) : null;
	// 	$options['endDate'] = !empty($options['endDate']) ? date('Y-m-d', strtotime($options['endDate'])) : null;
	// 	$currentFTE = !empty($options['currentFTE']) ? $options['currentFTE'] : 0;

	// 	if ($options['showAllFTE']) {
	// 		foreach ($this->fteOptions as $obj) {
	// 			$filterFTEOptions[$obj] = $obj;
	// 		}
	// 	} else {
	// 		$query = $this->_table->InstitutionSiteStaff->find();
	// 		$query->where(['AND' => ['institution_site_position_id' => $positionId]]);

	// 		if (!empty($options['startDate'])) {
	// 			$query->where(['AND' => ['OR' => [
	// 				'end_date >= ' => $options['startDate'],
	// 				'end_date is null'
	// 				]]]);
	// 		}

	// 		if (!empty($options['endDate'])) {
	// 			$query->where(['AND' => ['start_date <= ' => $options['endDate']]]);
	// 		}

	// 		$query->select([
	// 				// todo:mlee unable to implement 'COALESCE(SUM(FTE),0) as totalFTE'
	// 				'totalFTE' => $query->func()->sum('FTE'),
	// 				'institution_site_position_id'
	// 			])
	// 			->group('institution_site_position_id')
	// 		;

	// 		if (is_object($query)) {
	// 			$data = $query->toArray();
	// 			$totalFTE = empty($data[0]->totalFTE) ? 0 : $data[0]->totalFTE * 100;
	// 			$remainingFTE = 100 - intval($totalFTE);
	// 			$remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

	// 			if ($options['includeSelfNum']) {
	// 				$remainingFTE +=  $options['FTE_value'];
	// 			}
	// 			$highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

	// 			$filterFTEOptions = [];

	// 			foreach ($this->fteOptions as $obj) {
	// 				if ($highestFTE >= $obj) {
	// 					$objLabel = number_format($obj / 100, 2);
	// 					$filterFTEOptions[$obj] = $objLabel;
	// 				}
	// 			}

	// 			if(!empty($currentFTE) && !in_array($currentFTE, $filterFTEOptions)){
	// 				if($remainingFTE > 0) {
	// 					$newMaxFTE = $currentFTE + $remainingFTE;
	// 				}else{
	// 					$newMaxFTE = $currentFTE;
	// 				}
					
	// 				foreach ($this->fteOptions as $obj) {
	// 					if ($obj <= $newMaxFTE) {
	// 						$objLabel = number_format($obj / 100, 2);
	// 						$filterFTEOptions[$obj] = $objLabel;
	// 					}
	// 				}
	// 			}

	// 		}

	// 		if (count($filterFTEOptions)==0) {
	// 			$filterFTEOptions = array(''=>__('No available FTE'));
	// 		}
	// 	}
	// 	return $filterFTEOptions;
	// }

	// public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request) {
	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $this->_table->InstitutionSiteStaff->StaffTypes->getList();

	// 	return $attr;
	// }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		if (isset($entity->student_guardians)) {
			if (array_key_exists(0, $entity->student_guardians)) {
				if (array_key_exists('view', $buttons)) {
					$buttons['view']['url'][1] = $entity->student_guardians[0]->guardian_user_id;
				}
				if (array_key_exists('edit', $buttons)) {
					$buttons['edit']['url'][1] = $entity->student_guardians[0]->guardian_user_id;
				}
				if (array_key_exists('remove', $buttons)) {
					$buttons['remove']['attr']['field-value'] = $entity->student_guardians[0]->id;
				}
			}
		}

		// because this is a behavior, it will call appTable's onUpdateActionButtons again
		$event->stopPropagation();
		return $buttons;
	}

	public function onUpdateFieldGuardianRelationId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->StudentGuardians->GuardianRelations->getList();
		if (empty($attr['options']->toArray())){
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.staffTypeId');
		}
		return $attr;
	}

	public function onUpdateFieldGuardianEducationLevelId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->StudentGuardians->GuardianEducationLevels->getList();
		if (empty($attr['options']->toArray())){
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.staffTypeId');
		}
		return $attr;
	}


}
