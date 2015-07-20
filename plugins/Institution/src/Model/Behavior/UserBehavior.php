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
			$institutionId = 0;
		}
		$query
			->where(['institution_site_id = '.$institutionId])
			;
	}

	public function indexBeforeAction(Event $event) {
		if ($this->_table->hasBehavior('Student')) {
			$this->_table->ControllerAction->field('programme_section', []);
			$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'programme_section', 'student_status']);
		} else if ($this->_table->hasBehavior('Staff')) {
			$this->_table->ControllerAction->field('position', []);
			$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'position', 'staff_status']);
		}	
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
			'ControllerAction.Model.add.addOnReload' => 'onReload',
			'ControllerAction.Model.onBeforeDelete' => 'onBeforeDelete',
			'Model.custom.onUpdateActionButtons' => 'onUpdateActionButtons',
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
				'ControllerAction.Model.onUpdateFieldSecurityRoleId' => 'onUpdateFieldSecurityRoleId',
				'ControllerAction.Model.onUpdateFieldStartDate' => 'onUpdateFieldStartDate',
				'ControllerAction.Model.onUpdateFieldFTE' => 'onUpdateFieldFTE',
				'ControllerAction.Model.onUpdateFieldStaffTypeID' => 'onUpdateFieldStaffTypeID',
			];
		}

		$newEvents = array_merge($newEvents, $roleEvents);
		$events = array_merge($events,$newEvents);
		return $events;
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {


		$process = function() use ($id, $options) {
			// must also delete security roles here

			$entity = $this->associatedModel->get($id);
			$securityUserId = $entity->security_user_id;

			$remainingAssociatedCount = $this->associatedModel
				->find()
				->where([$this->associatedModel->aliasField('security_user_id') => $securityUserId])
				->count();
			if ($remainingAssociatedCount<=1) {
				// need to reinsert associated array so that still recognise user as a 'student' or 'staff'
				$newAssociated = $this->associatedModel->newEntity(['security_user_id' => $securityUserId]);
				$this->associatedModel->save($newAssociated);
			}

			// must also delete security roles here if it is a staff
			if ($this->_table->hasBehavior('Staff')) {
				$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

				if ($this->_table->Session->check('Institutions.id')) {
					$institutionId = $this->_table->Session->read('Institutions.id');
				}


				// if got security_user and institution then delete all
				if (isset($institutionId) && isset($securityUserId)) {
					$Institution = TableRegistry::get('Institution.Institutions');
					$institutionData = $Institution->get($institutionId);
					
					$securityGroupId = $institutionData->security_group_id;


					$conditions = [
									'security_group_id' => $securityGroupId,
									'security_user_id' => $securityUserId
									];	
					$SecurityGroupUsers->deleteAll($conditions);
				}
				

			}
			
			return $this->associatedModel->delete($entity, $options->getArrayCopy());
		};
		return $process;
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

			if ($this->_table->alias() == 'Staff') {
				$options['contain'] = [
					'InstitutionSiteStaff' => [
						'conditions' => [
							'InstitutionSiteStaff.institution_site_id' => $institutionId
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
			$institutionSiteId = $session->read('Institutions.id');
			$associationString = $this->_table->alias().'.'.$this->associatedModel->table().'.0.';
			$this->_table->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionSiteId, 'fieldName' => $associationString.'institution_site_id']);			

			if ($this->_table->hasBehavior('Student')) {

				$this->_table->ControllerAction->field('academic_period', ['fieldName' => $associationString.'academic_period']);
				$this->_table->ControllerAction->field('education_programme_id', ['fieldName' => $associationString.'education_programme_id']);
				$this->_table->ControllerAction->field('education_grade', ['fieldName' => $associationString.'education_grade']);
				$this->_table->ControllerAction->field('section', ['fieldName' => $associationString.'section']);
				$this->_table->ControllerAction->field('student_status_id', ['fieldName' => $associationString.'student_status_id']);
				$this->_table->ControllerAction->field('start_date', ['type' => 'date', 'fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('end_date', [
					'type' => 'date', 
					'fieldName' => $associationString.'end_date',
					'date_options' => ['startDate' => '+1d']
				]);
				// $this->_table->fields['end_date']['value'] = '09-07-2015';
				// $this->_table->fields['end_date’][‘date_options']['start_date'] = '+1d';
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
				$this->_table->ControllerAction->field('security_role_id', ['fieldName' => $associationString.'security_role_id']);
				$this->_table->ControllerAction->field('start_date', ['fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('FTE', ['fieldName' => $associationString.'FTE']);
				$this->_table->ControllerAction->field('staff_type_id', ['fieldName' => $associationString.'staff_type_id']);
				$this->_table->ControllerAction->field('start_date', ['type' => 'Date', 'fieldName' => $associationString.'start_date']);
				$this->_table->ControllerAction->field('search',['type' => 'autocomplete', 
															     'placeholder' => 'openEMIS ID or Name',
															     'url' => '/Institutions/Staff/autoCompleteUserList',
															     'length' => 3 ]);
				$this->_table->ControllerAction->setFieldOrder([
					'institution_site_position_id', 'security_role_id', 'start_date', 'FTE', 'staff_type_id'
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

						// need to insert security roles here
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

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionSiteId
		);

		$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = true;
		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.academicPeriod');
		}

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		$this->academicPeriodId = null;
		if (array_key_exists('academic_period', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['academic_period'])) {
				$this->academicPeriodId = key($this->_table->fields['academic_period']['options']);
				if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
					if (array_key_exists('academic_period', $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0])) {
						if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['academic_period']) {
							$this->academicPeriodId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['academic_period'];
						}
					}
				}

			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = true;
		$attr['options'] = [];
		if (isset($this->academicPeriodId)) {
			$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
			$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($institutionSiteId, $this->academicPeriodId);
			if (empty($attr['options'])) {
				$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.educationProgrammeId');
			}
		}

		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		if (array_key_exists('education_programme_id', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_programme_id'])) {
				$educationProgrammeId = key($this->_table->fields['education_programme_id']['options']);
				if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
					if (array_key_exists('education_programme_id', $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0])) {
						if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_programme_id']) {
							$educationProgrammeId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_programme_id'];
						}
					}
				}
			}
		}

		// this is used for staffTable autocomplete - for filtering of students that are (in institution and of same education programme)
		$session = $this->_table->request->session();
		$session->delete($this->_table->controller->name.'.'.$this->_table->alias);
		if (isset($educationProgrammeId)) {
			$institutionSiteId = $session->read('Institutions.id');
			$session->write($this->_table->controller->name.'.'.$this->_table->alias.'.'.'institution_site_id', $institutionSiteId);
			$session->write($this->_table->controller->name.'.'.$this->_table->alias.'.'.'education_programme_id', $educationProgrammeId);
		}

		$attr['type'] = 'select';
		$attr['onChangeReload'] = true;
		$attr['options'] = [];
		if (isset($educationProgrammeId)) {
			$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$attr['options'] = $InstitutionSiteGrades->getGradeOptions($institutionSiteId, $this->academicPeriodId, $educationProgrammeId);
		}

		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.institutionSiteGrades');
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		if (array_key_exists('education_grade', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_grade'])) {
				$this->education_grade = key($this->_table->fields['education_grade']['options']);
				if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
					if (array_key_exists('education_grade', $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0])) {
						if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_grade']) {
							$this->education_grade = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['education_grade'];
						}
					}
				}
			}
		}
		$attr['type'] = 'select';
		$attr['options'] = [];
		if (isset($this->education_grade)) {
			$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
			$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $institutionSiteId, $this->education_grade);
		}

		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.sections');
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->associatedModel->StudentStatuses->getList();

		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.studentStatusId');
		}

		return $attr;
	}

	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		$InstitutionSitePositions = TableRegistry::get('Institution.InstitutionSitePositions');
		$list = $InstitutionSitePositions->getInstitutionSitePositionList($institutionSiteId, true);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.institutionSitePositionId');
		}
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		$Institutions = TableRegistry::get('Institution.Institutions');
		$obj = $Institutions->get($institutionSiteId);
		$groupId = $obj->security_group_id;
		$userId = null;

		if ($session->read('Auth.User.super_admin') == 0) {
			$userId = $session->read('Auth.User.id');
		}

		$roleOptions = $this->_table->SecurityRoles->getPrivilegedRoleOptionsByGroup($groupId, $userId);

		$attr['type'] = 'select';
		$attr['options'] = $roleOptions;

		if (empty($attr['options'])) {
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.securityRoleId');
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldFTE(Event $event, array $attr, $action, $request) {
		if (array_key_exists('institution_site_position_id', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['institution_site_position_id'])) {
				$positionId = key($this->_table->fields['institution_site_position_id']['options']);
				if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
					if (array_key_exists('institution_site_position_id', $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0])) {
						if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['institution_site_position_id']) {
							$positionId = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['institution_site_position_id'];
						}
					}
				}
			}
		}

		// this is used for staffTable autocomplete - for filtering of staff that are (in institution and of same position)
		$session = $this->_table->request->session();
		$session->delete($this->_table->controller->name.'.'.$this->_table->alias);
		if ($positionId) {
			$institutionSiteId = $session->read('Institutions.id');
			$session->write($this->_table->controller->name.'.'.$this->_table->alias.'.'.'institution_site_id', $institutionSiteId);
			$session->write($this->_table->controller->name.'.'.$this->_table->alias.'.'.'institution_site_position_id', $positionId);
		}

		$startDate = null;
		if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
			if (array_key_exists('start_date', $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0])) {
				if ($this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['start_date']) {
					$startDate = $this->_table->request->data[$this->_table->alias()][$this->associatedModel->table()][0]['start_date'];
				}
			}
		}

		$attr['type'] = 'select';
		$attr['options'] = $this->getFTEOptions($positionId, ['startDate' => $startDate]);
		if (empty($attr['options'])) {
			$attr['attr']['empty'] = __('No available FTE');
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.FTE');
		}
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
				$filterFTEOptions = [];
			}
		}
		return $filterFTEOptions;
	}

	public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->_table->InstitutionSiteStaff->StaffTypes->getList();
		if (empty($attr['options'])){
			$this->_table->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.staffTypeId');
		}
		
		return $attr;
	}

	public function addOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		if ($this->_table->hasBehavior('Student')) {
			$options['associated'] = ['InstitutionSiteStudents' => ['validate' => false]];
		} 
		if ($this->_table->hasBehavior('Staff')) {
			$options['associated'] = ['InstitutionSiteStaff' => ['validate' => false]];
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);

		if ($this->_table->hasBehavior('Student')) {
			if (array_key_exists('remove', $buttons)) {
				if (array_key_exists('removeStraightAway', $buttons['remove']) && $buttons['remove']['removeStraightAway']) {
					// pr($entity);
					if (isset($entity->institution_site_students)) {
						if (array_key_exists(0, $entity->institution_site_students)) {
							$buttons['remove']['attr']['field-value'] = $entity->institution_site_students[0]->id;
						}
					}
				}
			}
			// because this is a behavior, it will call appTable's onUpdateActionButtons again
			$event->stopPropagation();
		}
		if ($this->_table->hasBehavior('Staff')) {
			if (array_key_exists('remove', $buttons)) {
				if (array_key_exists('removeStraightAway', $buttons['remove']) && $buttons['remove']['removeStraightAway']) {
					// pr($entity);
					if (isset($entity->institution_site_staff)) {
						if (array_key_exists(0, $entity->institution_site_staff)) {
							$buttons['remove']['attr']['field-value'] = $entity->institution_site_staff[0]->id;
						}
					}
				}
			}
			// because this is a behavior, it will call appTable's onUpdateActionButtons again
			$event->stopPropagation();
		}
		
		
		return $buttons;
	}

}
