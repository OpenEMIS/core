<?php
namespace Institution\Controller;

use Institution\Controller\AppController;
use Cake\Event\Event;

class InstitutionsController extends AppController  {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.Institutions');
		$this->ControllerAction->models = [
			'Attachments' 		=> ['className' => 'Institution.InstitutionSiteAttachments'],
			'Additional' 		=> ['className' => 'Institution.Additional'],
			'History' 			=> ['className' => 'Institution.InstitutionSiteActivities', 'actions' => ['index']],

			// 'InstitutionSiteCustomField',
			// 'InstitutionSiteCustomFieldOption',

			'Positions' 		=> ['className' => 'Institution.InstitutionSitePositions'],
			'Programmes' 		=> ['className' => 'Institution.InstitutionSiteProgrammes'],
			'Shifts' 			=> ['className' => 'Institution.InstitutionSiteShifts'],
			'Sections' 			=> ['className' => 'Institution.InstitutionSiteSections'],
			// 'Classes' 			=> ['className' => 'Institution.InstitutionSiteSectionClasses'],
			'Classes' 			=> ['className' => 'Institution.InstitutionSiteClasses'],
			'Infrastructures' 	=> ['className' => 'Institution.InstitutionSiteInfrastructures'],

			'Staff' 			=> ['className' => 'Institution.InstitutionSiteStaff'],
			'StaffAbsences' 	=> ['className' => 'Institution.InstitutionSiteStaffAbsences'],
			'StaffBehaviours' 	=> ['className' => 'Institution.StaffBehaviours'],

			'AssessmentResults' => ['className' => 'Institution.AssessmentResults'],

			'Students' 			=> ['className' => 'Institution.InstitutionSiteStudents'],
			'StudentAbsences' 	=> ['className' => 'Institution.InstitutionSiteStudentAbsences'],
			'StudentAttendance' => ['className' => 'Institution.StudentAttendance', 'actions' => ['index']],
			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],

			'BankAccounts' 		=> ['className' => 'Institution.InstitutionSiteBankAccounts'],
			'Fees' 				=> ['className' => 'Institution.InstitutionSiteFees'],
			'StudentFees' 		=> ['className' => 'Institution.StudentFees'],

			// Surveys
			'Surveys' 			=> ['className' => 'Institution.InstitutionSurveys'],

			// Quality
			// 'Fees' => ['className' => 'Institution.InstitutionSiteFees'],
			// 'Fees' => ['className' => 'Institution.InstitutionSiteFees'],

		];
		$this->loadComponent('Paginator');
		
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];

		if ($action == 'index') {
			$session->delete('Institutions.id');
		}

		if ($session->check('Institutions.id') || $action == 'view') {
			$id = 0;
			if ($session->check('Institutions.id')) {
				$id = $session->read('Institutions.id');
			} else if (isset($this->request->pass[0])) {
				$id = $this->request->pass[0];
			}
			if (!empty($id)) {
				$obj = $this->Institutions->get($id);
				$name = $obj->name;
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]);
			} else {
				return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
			}
		}

		$header = __('Institution');
		$this->set('contentHeader', $header);
	}

	public function onInitialize($event, $model) {
		$session = $this->request->session();
		$header = __('Institution');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);

		if (array_key_exists('institution_site_id', $model->fields)) {
			if (!$session->check('Institutions.id')) {
				$this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
			} else {
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('Institutions.id');
			}
		}
		
		$this->set('contentHeader', $header);
	}

	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if (array_key_exists('institution_site_id', $model->fields)) {
			if (!$session->check('Institutions.id')) {
				$this->Alert->error('general.notExists');
			}
			$options['conditions'][] = ['Institutions.id' => $session->read('Institutions.id')];
		}
		
		return $options;
	}
}
