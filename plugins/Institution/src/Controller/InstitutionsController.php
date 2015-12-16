<?php
namespace Institution\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

use Institution\Controller\AppController;

class InstitutionsController extends AppController  {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.Institutions');
		$this->ControllerAction->models = [
			'Attachments' 		=> ['className' => 'Institution.InstitutionAttachments'],
			'History' 			=> ['className' => 'Institution.InstitutionActivities', 'actions' => ['search', 'index']],

			'Positions' 		=> ['className' => 'Institution.InstitutionPositions', 'options' => ['deleteStrategy' => 'transfer']],
			'Programmes' 		=> ['className' => 'Institution.InstitutionGrades'],
			'Shifts' 			=> ['className' => 'Institution.InstitutionShifts'],
			'Sections' 			=> ['className' => 'Institution.InstitutionSections'],
			'Classes' 			=> ['className' => 'Institution.InstitutionClasses'],
			'Infrastructures' 	=> ['className' => 'Institution.InstitutionInfrastructures'],

			'Staff' 			=> ['className' => 'Institution.Staff'],
			'StaffUser' 		=> ['className' => 'Institution.StaffUser', 'actions' => ['add', 'view', 'edit']],
			'StaffAccount' 		=> ['className' => 'Institution.StaffAccount', 'actions' => ['view', 'edit']],
			'StaffAbsences' 	=> ['className' => 'Institution.StaffAbsences'],
			'StaffAttendances' 	=> ['className' => 'Institution.StaffAttendances', 'actions' => ['index']],
			'StaffBehaviours' 	=> ['className' => 'Institution.StaffBehaviours'],
			'StaffPositions' 	=> ['className' => 'Institution.StaffPositions'],

			'Students' 			=> ['className' => 'Institution.Students'],
			'StudentUser' 		=> ['className' => 'Institution.StudentUser', 'actions' => ['add', 'view', 'edit']],
			'StudentAccount' 	=> ['className' => 'Institution.StudentAccount', 'actions' => ['view', 'edit']],
			'StudentSurveys' 	=> ['className' => 'Student.StudentSurveys', 'actions' => ['index', 'view', 'edit']],
			'StudentAbsences' 	=> ['className' => 'Institution.InstitutionStudentAbsences'],
			'StudentAttendances'=> ['className' => 'Institution.StudentAttendances', 'actions' => ['index']],
			'AttendanceExport'	=> ['className' => 'Institution.AttendanceExport', 'actions' => ['excel']],
			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'Assessments' 		=> ['className' => 'Institution.InstitutionAssessments', 'actions' => ['index', 'view', 'edit', 'remove']],
			'Promotion' 		=> ['className' => 'Institution.StudentPromotion', 'actions' => ['add']],
			'Transfer' 			=> ['className' => 'Institution.StudentTransfer', 'actions' => ['index', 'add']],
			'TransferApprovals' => ['className' => 'Institution.TransferApprovals', 'actions' => ['edit', 'view']],
			'StudentDropout' 	=> ['className' => 'Institution.StudentDropout', 'actions' => ['index', 'edit', 'view']],
			'DropoutRequests' 	=> ['className' => 'Institution.DropoutRequests', 'actions' => ['add', 'edit', 'remove']],
			'TransferRequests' 	=> ['className' => 'Institution.TransferRequests', 'actions' => ['index', 'view', 'add', 'edit', 'remove']],
			'StudentAdmission'	=> ['className' => 'Institution.StudentAdmission', 'actions' => ['index', 'edit', 'view']],

			'BankAccounts' 		=> ['className' => 'Institution.InstitutionBankAccounts'],
			'Fees' 				=> ['className' => 'Institution.InstitutionFees'],
			'StudentFees' 		=> ['className' => 'Institution.StudentFees', 'actions' => ['index', 'view', 'add']],

			// Surveys
			'Surveys' 			=> ['className' => 'Institution.InstitutionSurveys', 'actions' => ['index', 'view', 'edit', 'remove']],

			// Quality
			'Rubrics' 			=> ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
			'RubricAnswers' 	=> ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],
			'Visits' 			=> ['className' => 'Institution.InstitutionQualityVisits'],

			'ImportInstitutions' => ['className' => 'Institution.ImportInstitutions', 'actions' => ['index', 'add']],
			'ImportStaffAttendances' => ['className' => 'Institution.ImportStaffAttendances', 'actions' => ['index', 'add']],
			'ImportStudentAttendances' => ['className' => 'Institution.ImportStudentAttendances', 'actions' => ['index', 'add']],
			'ImportInstitutionSurveys' => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['index', 'add']],
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Institutions');

		// this is to cater for back links
		$query = $this->request->query;

		if (array_key_exists('institution_id', $query)) {
			//check for permission
			if (!$this->AccessControl->isAdmin()) {
				$institutionIds = $this->AccessControl->getInstitutionsByUser();

				if (!array_key_exists($query['institution_id'], $institutionIds)) {
					$this->Alert->error('security.noAccess');
					$refererUrl = $this->request->referer();
					$event->stopPropagation();
					return $this->redirect($refererUrl);
				}
			}
			$session->write('Institution.Institutions.id', $query['institution_id']);
		}

		if ($action == 'index') {
			$session->delete('Institution.Institutions.id');
		}

		if ($session->check('Institution.Institutions.id') || in_array($action, ['view', 'edit', 'dashboard'])) {
			$id = 0;
			if (isset($this->request->pass[0]) && (in_array($action, ['view', 'edit', 'dashboard']))) {
				$id = $this->request->pass[0];
			} else if ($session->check('Institution.Institutions.id')) {
				$id = $session->read('Institution.Institutions.id');
			}
			if (!empty($id)) {
				$this->activeObj = $this->Institutions->get($id);
				$name = $this->activeObj->name;
				if ($action == 'dashboard') {
					$session->write('Institution.Institutions.id', $id);
					$session->write('Institution.Institutions.name', $name);
				}
				if ($action == 'view') {
					$header = $name .' - '.__('Overview');
				} else {
					$header = $name .' - '.__(Inflector::humanize($action));
				}
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $id]);
			} else {
				return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model) {
		if (!is_null($this->activeObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			$persona = false;
			$alias = $model->alias;
			// temporary fix for renaming Sections and Classes
			if ($alias == 'Sections') $alias = 'Classes';
			else if ($alias == 'Classes') $alias = 'Subjects';

			if ($action) {
				/**
				 * replaced 'action' => $alias to 'action' => $model->alias,
				 * since only the name changes but not url
				 */
				$this->Navigation->addCrumb($model->getHeader($alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
				if (strtolower($action) != 'index')	{
					if (in_array('Staff', $model->behaviors()->loaded()) || in_array('Student', $model->behaviors()->loaded())) {
						if (isset($params['pass'][1])) {
							$persona = $model->get($params['pass'][1]);
							if (is_object($persona)) {
								$this->Navigation->addCrumb($persona->name);
							}
						}
					} else {
						$this->Navigation->addCrumb(ucwords($action));
					}
				}
			} else {
				$this->Navigation->addCrumb($model->getHeader($alias));
			}

			$header = $this->activeObj->name;
			if ($persona) {
				$header .= ' - ' . $persona->name;
			} else {
				$header .= ' - ' . $model->getHeader($alias);
			}

			if ($model->hasField('institution_id') && !in_array($model->alias(), ['TransferRequests'])) {
				$model->fields['institution_id']['type'] = 'hidden';
				$model->fields['institution_id']['value'] = $session->read('Institution.Institutions.id');
			}

			if ($model->hasField('institution_id') && !is_null($this->activeObj)) {
				$model->fields['institution_id']['type'] = 'hidden';
				$model->fields['institution_id']['value'] = $session->read('Institution.Institutions.id');
				/**
				 * set sub model's institution id here
				 */
				$model->institutionId = $this->activeObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('institution_id') => $this->activeObj->id
					]);
				
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */

					// replaced 'action' => $alias to 'action' => $model->alias, since only the name changes but not url
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
					}
				}
			}

			$this->set('contentHeader', $header);
		} else {
			// pr($model->alias());die;
			if ($model->alias() == 'ImportInstitutions') {
				$this->Navigation->addCrumb($model->getHeader($model->alias()));
				$header = __('Institutions') . ' - ' . $model->getHeader($model->alias());
				$this->set('contentHeader', $header);
			} else {
				$this->Alert->warning('general.notExists');
				$event->stopPropagation();
				return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
			}
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if (!$this->request->is('ajax')) {
			if ($model->hasField('institution_id')) {
				if (!$session->check('Institution.Institutions.id')) {
					$this->Alert->error('general.notExists');
					// should redirect
				} else {
					$query->where([$model->aliasField('institution_id') => $session->read('Institution.Institutions.id')]);
				}
			}
		}
	}

	public function excel($id=0) {
		$this->Institutions->excel($id);
		$this->autoRender = false;
	}

	public function dashboard() {
		if ($this->activeObj) {
			$id = $this->activeObj->id;
			$this->ControllerAction->model->action = $this->request->action;

			// $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
			$highChartDatas = [];

			//Students By Year
			$params = array(
				'conditions' => array('institution_id' => $id)
			);
			$InstitutionStudents = TableRegistry::get('Institution.Students');
			$highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_year', $params);
			
			//Students By Grade for current year
			$params = array(
				'conditions' => array('institution_id' => $id)
			);

			$highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_grade', $params);

			//Staffs By Position for current year
			$params = array(
				'conditions' => array('institution_id' => $id)
			);
			$InstitutionStaff = TableRegistry::get('Institution.Staff');
			$highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff', $params);

			$this->set('highChartDatas', $highChartDatas);

		} else {
			return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}
	}

	public function getUserTabElements($options = []) {
		$userRole = (array_key_exists('userRole', $options))? $options['userRole']: null;
		$action = (array_key_exists('action', $options))? $options['action']: 'add';
		$id = (array_key_exists('id', $options))? $options['id']: 0;
		$userId = (array_key_exists('userId', $options))? $options['userId']: 0;
		$type = 'Students';

		switch ($userRole) {
			case 'Staff':
				$pluralUserRole = 'Staff'; // inflector unable to handle
				$type = 'Staff';
				break;
			default:
				$pluralUserRole = Inflector::pluralize($userRole);
				break;
		}

		$url = ['plugin' => $this->plugin, 'controller' => $this->name];
		$studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];

		$tabElements = [
			$pluralUserRole => ['text' => __('Academic')],
			$userRole.'User' => ['text' => __('Overview')],
			$userRole.'Account' => ['text' => __('Account')], 
			
			// $userRole.'Nationality' => ['text' => __('Identities')],
		];

		$studentTabElements = [
			'Identities' => ['text' => __('Identities')],
			'Nationalities' => ['text' => __('Nationalities')],
			'Contacts' => ['text' => __('Contacts')],
			'Guardians' => ['text' => __('Guardians')],
			'Languages' => ['text' => __('Languages')],
			'SpecialNeeds' => ['text' => __('Special Needs')],
			'Attachments' => ['text' => __('Attachments')],
			'Comments' => ['text' => __('Comments')],
			'History' => ['text' => __('History')],
		];

		if ($type == 'Staff') {
			$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
			unset($studentTabElements['Guardians']);
		}

		$tabElements = array_merge($tabElements, $studentTabElements);

		if ($action == 'add') {
			$tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'add']);
			$tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'add']);
			$tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'add']);
		} else {
			unset($tabElements[$pluralUserRole]);
			// $tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'view']);
			$tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'view']);
			$tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);
			
			// $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);

			// Only Student has Survey tab
			if ($userRole == 'Student') {
				$tabElements[$userRole.'Surveys'] = ['text' => __('Survey')];
				$tabElements[$userRole.'Surveys']['url'] = array_merge($url, ['action' => $userRole.'Surveys', 'index']);
			}

			foreach ($studentTabElements as $key => $value) {
				$tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
			}
		}

		foreach ($tabElements as $key => $tabElement) {
			switch ($key) {
				case $userRole.'User':
					$params = [$userId];
					break;
				case $userRole.'Account':
					$params = [$userId];
					break;
				case $userRole.'Surveys':
					$params = ['user_id' => $userId];
					break;
				default:
					$params = [$id];
			}
			$tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], $params);
		}

		$session = $this->request->session();
		$session->write('Institution.'.$type.'.tabElements', $tabElements);

		return $tabElements;
	}

}
