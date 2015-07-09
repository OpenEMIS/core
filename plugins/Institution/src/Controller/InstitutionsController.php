<?php
namespace Institution\Controller;

use ArrayObject;

use Cake\Event\Event;
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
			'Attachments' 		=> ['className' => 'Institution.InstitutionSiteAttachments'],
			'History' 			=> ['className' => 'Institution.InstitutionSiteActivities', 'actions' => ['search', 'index']],

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
			'StaffAbsences' 	=> ['className' => 'Institution.StaffAbsences'],
			'StaffAttendance' 	=> ['className' => 'Institution.StaffAttendance', 'actions' => ['index']],
			'StaffBehaviours' 	=> ['className' => 'Institution.StaffBehaviours'],

			'Students' 			=> ['className' => 'Institution.Students'],
			'StudentAbsences' 	=> ['className' => 'Institution.InstitutionSiteStudentAbsences'],
			'StudentAttendance' => ['className' => 'Institution.StudentAttendance', 'actions' => ['index']],
			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'StudentResults'	=> ['className' => 'Institution.StudentResults', 'actions' => ['index']],

			'Assessments'		=> ['className' => 'Institution.AssessmentStatuses', 'actions' => ['index', 'view']],

			'BankAccounts' 		=> ['className' => 'Institution.InstitutionSiteBankAccounts'],
			'Fees' 				=> ['className' => 'Institution.InstitutionSiteFees'],
			'StudentFees' 		=> ['className' => 'Institution.StudentFees'],

			// Surveys
			'Surveys' 			=> ['className' => 'Institution.InstitutionSurveys', 'actions' => ['!add']],

			// Quality
			'Rubrics' 			=> ['className' => 'Institution.InstitutionRubrics', 'actions' => ['!add']],
			'Visits' 			=> ['className' => 'Institution.InstitutionQualityVisits']
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

		if ($session->check('Institutions.id') || in_array($action, ['view', 'edit', 'dashboard'])) {
			$id = 0;
			if (isset($this->request->pass[0]) && (in_array($action, ['view', 'edit', 'dashboard']))) {
				$id = $this->request->pass[0];
			} else if ($session->check('Institutions.id')) {
				$id = $session->read('Institutions.id');
			}
			if (!empty($id)) {
				if ($action == 'dashboard') {
					$session->write('Institutions.id', $id);
				}
				$this->activeObj = $this->Institutions->get($id);
				$name = $this->activeObj->name;
				if ($action == 'view') {
					$header = $name .' - '.__('Overview');
				} else {
					$header = $name .' - '.__(Inflector::humanize($action));
				}
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $action, $id]);
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

			$header = $this->activeObj->name;
			if ($persona) {
				$header .= ' - ' . $persona->name;
			} else {
				$header .= ' - ' . $model->getHeader($model->alias);
			}

			if ($model->hasField('institution_site_id') && !is_null($this->activeObj)) {
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('Institutions.id');
				/**
				 * set sub model's institution id here
				 */
				$model->institutionId = $this->activeObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('institution_site_id') => $this->activeObj->id
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

		if ($model->hasField('institution_site_id')) {
			if (!$session->check('Institutions.id')) {
				$this->Alert->error('general.notExists');
			}
			$options['conditions'][$model->aliasField('institution_site_id')] = $session->read('Institutions.id');
		}
		
		return $options;
	}

	public function dashboard() {
		if ($this->activeObj) {
			$id = $this->activeObj->id;
			$this->ControllerAction->model->action = $this->request->action;

			// $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
			$highChartDatas = [];

			//Students By Year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
			$highChartDatas[] = $InstitutionSiteStudents->getHighChart('number_of_students_by_year', $params);
			
			//Students By Grade for current year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$highChartDatas[] = $InstitutionSiteSectionStudents->getHighChart('number_of_students_by_grade', $params);

			//Staffs By Position for current year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');
			$highChartDatas[] = $InstitutionSiteStaff->getHighChart('number_of_staff', $params);

			$this->set('highChartDatas', $highChartDatas);

		} else {
			return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}
	}

}
