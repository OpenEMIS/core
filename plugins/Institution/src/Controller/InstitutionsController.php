<?php
namespace Institution\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Institution\Controller\AppController;

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
			'Infrastructures' 	=> ['className' => 'Institution.InstitutionInfrastructures'],

			// 'Accounts' 			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Staff' 			=> ['className' => 'Institution.Staff'],
			'StaffAbsences' 	=> ['className' => 'Institution.InstitutionSiteStaffAbsences'],
			'StaffBehaviours' 	=> ['className' => 'Institution.StaffBehaviours'],

			'Students' 			=> ['className' => 'Institution.Students'],
			'StudentAbsences' 	=> ['className' => 'Institution.InstitutionSiteStudentAbsences'],
			'StudentAttendance' => ['className' => 'Institution.StudentAttendance', 'actions' => ['index']],
			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'StudentResults'	=> ['className' => 'Institution.StudentResults', 'actions' => ['index']],

			'BankAccounts' 		=> ['className' => 'Institution.InstitutionSiteBankAccounts'],
			'Fees' 				=> ['className' => 'Institution.InstitutionSiteFees'],
			'StudentFees' 		=> ['className' => 'Institution.StudentFees'],

			// Surveys
			'Surveys' 			=> ['className' => 'Institution.InstitutionSurveys', 'actions' => ['index', 'view', 'edit', 'remove']],

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

	public function onInitialize(Event $event, Table $model) {
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

		if ($model->hasField('institution_site_id')) {
			if (count($this->request->pass) > 1) {
				$institutionId = $session->read('Institutions.id');
				$modelId = $this->request->pass[1]; // id of the model

				$exists = $model->exists([
					$model->aliasField($model->primaryKey()) => $modelId,
					$model->aliasField('institution_site_id') => $institutionId
				]);

				if (!$exists) {
					$this->Alert->warning('general.notExists');
					return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
				}
			}
		}
		
		$this->set('contentHeader', $header);
	}

	public function beforePaginate(Event $event, Table $model, ArrayObject $options) {
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
