<?php
namespace Institution\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

use Institution\Controller\AppController;

class InstitutionsController extends AppController  {
	private $_institutionObj = null;

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
		$header = __('Institutions');

		if ($action == 'index') {
			$session->delete('Institutions.id');
		}

		if ($session->check('Institutions.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Institutions.id')) {
				$id = $session->read('Institutions.id');
			}
			if (!empty($id)) {
				$this->_institutionObj = $this->Institutions->get($id);
				$name = $this->_institutionObj->name;
				$header = $name .' - Overview';
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $action, $id]);
			} else {
				return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model) {
		if (!is_null($this->_institutionObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			$persona = false;
			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
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
				$this->Navigation->addCrumb($model->getHeader($model->alias));
			}

			$header = __($this->_institutionObj->name);
			if ($persona) {
				$header .= ' - ' . $persona->name;
			} else {
				$header .= ' - ' . $model->getHeader($model->alias);
			}

			if ($model->hasField('institution_site_id') && !is_null($this->_institutionObj)) {
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('Institutions.id');
				/**
				 * set sub model's institution id here
				 */
				$model->institutionId = $this->_institutionObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('institution_site_id') => $this->_institutionObj->id
					]);
				
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
					}
				}
			}

			$this->set('contentHeader', $header);
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}
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
