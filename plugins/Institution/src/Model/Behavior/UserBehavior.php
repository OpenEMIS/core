<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Network\Request;
use Cake\Controller\Controller;

class UserBehavior extends Behavior {
	public $fteOptions = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
	private $associatedModel;
	public function initialize(array $config) {
		$this->associatedModel = (array_key_exists('associatedModel', $config))? $config['associatedModel']: null;
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$session = $this->_table->request->session();
		if ($session->check('Institutions.id')) {
			$institutionId = $session->read('Institutions.id');
		} else {
			/**
			 * this should be something else
			 */
			$institutionId = 0;
		}
		$query
			->where(['institution_site_id = '.$institutionId])
			;
	}

	public function indexBeforeAction(Event $event) {
		if ($this->_table->hasBehavior('Student')) {

			$this->_table->fields['institution_name']['visible'] = false;
			$this->_table->ControllerAction->field('programmeSection', []);
			$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'programmeSection', 'student_status']);
		} else if ($this->_table->hasBehavior('Staff')) {
			$this->_table->fields['institution_name']['visible'] = false;
			$this->_table->ControllerAction->field('position', []);

		}	
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvents = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
			'ControllerAction.Model.add.afterSaveRedirect' => 'addAfterSaveRedirect',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
		];

		$roleEvents = [];

		if ($this->_table->hasBehavior('Student')) {
			$roleEvents = [
				'ControllerAction.Model.onUpdateFieldAcademicPeriod' => 'onUpdateFieldAcademicPeriod',
				'ControllerAction.Model.onUpdateFieldEducationProgrammeId' => 'onUpdateFieldEducationProgrammeId',
				'ControllerAction.Model.onUpdateFieldEducationGrade' => 'onUpdateFieldEducationGrade',
				'ControllerAction.Model.onUpdateFieldSection' => 'onUpdateFieldSection',
				'ControllerAction.Model.onUpdateFieldStudentStatusId' => 'onUpdateFieldStudentStatusId',
			];
		}

		if ($this->_table->hasBehavior('Staff')) {
			$roleEvents = [
				'ControllerAction.Model.onUpdateFieldInstitutionSitePositionId' => 'onUpdateFieldInstitutionSitePositionId',
				'ControllerAction.Model.onUpdateFieldStartDate' => 'onUpdateFieldStartDate',
				'ControllerAction.Model.onUpdateFieldFTE' => 'onUpdateFieldFTE',
				'ControllerAction.Model.onUpdateFieldStaffTypeID' => 'onUpdateFieldStaffTypeID',
			];
		}

		$newEvents = array_merge($newEvents, $roleEvents);
		$events = array_merge($events,$newEvents);
		return $events;
	}


	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		if ($this->_table->Session->check('Institutions.id')) {
			$institutionId = $this->_table->Session->read('Institutions.id');
			if ($this->_table->alias() == 'Students') {
				$options['contain'] = [
					'InstitutionSiteStudents' => [
						'conditions' => [
							'InstitutionSiteStudents.institution_site_id' => $institutionId
						]
					]
				];
			}
		}
		
		// if ($this->alias() == 'Staff') {
		// 	$options['contain'] = ['InstitutionSiteStaff' => ['conditions' => ['InstitutionSiteStudents.institution_site_id' => $institutionId]]];
		// }
	}


	public function addBeforeAction(Event $event) {
		if (array_key_exists('new', $this->_table->request->query)) {

		} else {
			foreach ($this->_table->fields as $key => $value) {
				$this->_table->fields[$key]['visible'] = false;
			}
			$session = $this->_table->request->session();
			$institutionsId = $session->read('Institutions.id');
			$associationString = $this->_table->alias().'.'.$this->associatedModel->table().'.0.';
			$this->_table->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionsId, 'fieldName' => $associationString.'institution_site_id']);			

			if ($this->_table->hasBehavior('Student')) {

				$this->_table->ControllerAction->field('academic_period', ['fieldName' => $associationString.'academic_period']);
				$this->_table->ControllerAction->field('education_programme_id', ['fieldName' => $associationString.'education_programme_id']);
				$this->_table->ControllerAction->field('education_grade', ['fieldName' => $associationString.'education_grade']);
				$this->_table->ControllerAction->field('section', ['fieldName' => $associationString.'section']);
				$this->_table->ControllerAction->field('student_status_id', ['fieldName' => $associationString.'student_status_id']);
				$this->_table->ControllerAction->field('start_date', ['type' => 'Date', 'fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('end_date', ['type' => 'Date', 'fieldName' => $associationString.'end_date']);
				$this->_table->ControllerAction->field('search',['type' => 'autocomplete', 
															     'placeholder' => 'openEMIS ID or Name',
															     'url' => '/Institutions/Students/autoCompleteUserList',
															     'length' => 3 ]);

				$this->_table->ControllerAction->setFieldOrder([
						'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
					, 'search'
					]);	
			}

			if ($this->_table->hasBehavior('Staff')) {
				$this->_table->ControllerAction->field('institution_site_position_id', ['fieldName' => $associationString.'institution_site_position_id']);
				$this->_table->ControllerAction->field('start_date', ['fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('FTE', ['fieldName' => $associationString.'FTE']);
				$this->_table->ControllerAction->field('staff_type_id', ['fieldName' => $associationString.'staff_type_id']);
				$this->_table->ControllerAction->field('start_date', ['type' => 'Date', 'fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('search',['type' => 'autocomplete', 
															     'placeholder' => 'openEMIS ID or Name',
															     'url' => '/Institutions/Staff/autoCompleteUserList',
															     'length' => 3 ]);
				$this->_table->ControllerAction->setFieldOrder([
					'institution_site_position_id', 'start_date', 'FTE', 'staff_type_id'
					, 'search'
					]);

			}
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
					if ($this->associatedModel->save($this->associatedModel->newEntity($data[$this->_table->alias()][$this->associatedModel->table()][0]))) {
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

	public function addAfterSave(Event $event, Controller $controller) {
		if (array_key_exists('new', $action)) {
			$session = $controller->request->session();
			$sessionVar = $this->_table->alias().'.add';
			$session->delete($sessionVar);
			unset($action['new']);
		}
		$event->stopPropagation();
		return $controller->redirect($action);
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);

		$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'true';

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');
		$this->academicPeriodId = null;
		if (array_key_exists('academic_period', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['academic_period'])) {
				$this->academicPeriodId = key($this->_table->fields['academic_period']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('academic_period'))) {
					$this->academicPeriodId = $this->_table->request->data($this->associatedModel->aliasField('academic_period'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';
		if (isset($this->academicPeriodId)) {
			$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
			$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($institutionsId, $this->academicPeriodId);
		}

		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');

		if (array_key_exists('education_programme_id', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_programme_id'])) {
				$this->educationProgrammeId = key($this->_table->fields['education_programme_id']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('education_programme_id'))) {
					$this->educationProgrammeId = $this->_table->request->data($this->associatedModel->aliasField('education_programme_id'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';

		if (isset($this->educationProgrammeId)) {
			$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$attr['options'] = $InstitutionSiteGrades->getGradeOptions($institutionsId, $this->academicPeriodId, $this->educationProgrammeId);
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');

		if (array_key_exists('education_grade', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_grade'])) {
				$this->education_grade = key($this->_table->fields['education_grade']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('education_grade'))) {
					$this->education_grade = $this->_table->request->data($this->associatedModel->aliasField('education_grade'));
				}
			}
		}
		$attr['type'] = 'select';

		if (isset($this->education_grade)) {
			$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
			$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $institutionsId, $this->education_grade);
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->associatedModel->StudentStatuses->getList();

		return $attr;
	}

	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');

		$InstitutionSitePositions = TableRegistry::get('Institution.InstitutionSitePositions');
		$list = $InstitutionSitePositions->getInstitutionSitePositionList($institutionsId, true);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'true';

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'true';
		return $attr;
	}

	public function onUpdateFieldFTE(Event $event, array $attr, $action, $request) {
		if (array_key_exists('institution_site_position_id', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['institution_site_position_id'])) {
				$positionId = key($this->_table->fields['institution_site_position_id']['options']);
				if ($this->_table->request->data($this->_table->aliasField('institution_site_position_id'))) {
					$positionId = $this->_table->request->data($this->_table->aliasField('institution_site_position_id'));
				}
			}
		}

		$startDate = null;
		if ($this->_table->request->data($this->_table->aliasField('start_date'))) {
			$startDate = $this->_table->request->data($this->_table->aliasField('start_date'));
		}

		$attr['type'] = 'select';
		$attr['options'] = $this->getFTEOptions($positionId, ['startDate' => $startDate]);
		return $attr;
	}

	public function getFTEOptions($positionId, $options = []) {
		$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
		$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
		$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
		$options['startDate'] = !empty($options['startDate']) ? date('Y-m-d', strtotime($options['startDate'])) : null;
		$options['endDate'] = !empty($options['endDate']) ? date('Y-m-d', strtotime($options['endDate'])) : null;
		$currentFTE = !empty($options['currentFTE']) ? $options['currentFTE'] : 0;

		if ($options['showAllFTE']) {
			foreach ($this->fteOptions as $obj) {
				$filterFTEOptions[$obj] = $obj;
			}
		} else {
			$query = $this->_table->InstitutionSiteStaff->find();
			$query->where(['AND' => ['institution_site_position_id' => $positionId]]);

			if (!empty($options['startDate'])) {
				$query->where(['AND' => ['OR' => [
					'end_date >= ' => $options['startDate'],
					'end_date is null'
					]]]);
			}

			if (!empty($options['endDate'])) {
				$query->where(['AND' => ['start_date <= ' => $options['endDate']]]);
			}

			$query->select([
					// todo:mlee unable to implement 'COALESCE(SUM(FTE),0) as totalFTE'
					'totalFTE' => $query->func()->sum('FTE'),
					'institution_site_position_id'
				])
				->group('institution_site_position_id')
			;

			if (is_object($query)) {
				$data = $query->toArray();
				$totalFTE = empty($data[0]->totalFTE) ? 0 : $data[0]->totalFTE * 100;
				$remainingFTE = 100 - intval($totalFTE);
				$remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

				if ($options['includeSelfNum']) {
					$remainingFTE +=  $options['FTE_value'];
				}
				$highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

				$filterFTEOptions = [];

				foreach ($this->fteOptions as $obj) {
					if ($highestFTE >= $obj) {
						$objLabel = number_format($obj / 100, 2);
						$filterFTEOptions[$obj] = $objLabel;
					}
				}

				if(!empty($currentFTE) && !in_array($currentFTE, $filterFTEOptions)){
					if($remainingFTE > 0) {
						$newMaxFTE = $currentFTE + $remainingFTE;
					}else{
						$newMaxFTE = $currentFTE;
					}
					
					foreach ($this->fteOptions as $obj) {
						if ($obj <= $newMaxFTE) {
							$objLabel = number_format($obj / 100, 2);
							$filterFTEOptions[$obj] = $objLabel;
						}
					}
				}

			}

			if (count($filterFTEOptions)==0) {
				$filterFTEOptions = array(''=>__('No available FTE'));
			}
		}
		return $filterFTEOptions;
	}

	public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->InstitutionSiteStaff->StaffTypes->getList();

		return $attr;
	}


}
