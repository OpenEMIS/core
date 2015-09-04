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
			'Attachments' 		=> ['className' => 'Institution.InstitutionSiteAttachments'],
			'History' 			=> ['className' => 'Institution.InstitutionSiteActivities', 'actions' => ['search', 'index']],

			'Positions' 		=> ['className' => 'Institution.InstitutionSitePositions', 'options' => ['deleteStrategy' => 'transfer']],
			'Programmes' 		=> ['className' => 'Institution.InstitutionGrades'],
			'Shifts' 			=> ['className' => 'Institution.InstitutionSiteShifts'],
			'Sections' 			=> ['className' => 'Institution.InstitutionSiteSections'],
			'Classes' 			=> ['className' => 'Institution.InstitutionSiteClasses'],
			'Infrastructures' 	=> ['className' => 'Institution.InstitutionInfrastructures'],

			'Staff' 			=> ['className' => 'Institution.Staff'],
			'StaffUser' 		=> ['className' => 'Institution.StaffUser', 'actions' => ['add', 'view', 'edit']],
			'StaffAbsences' 	=> ['className' => 'Institution.StaffAbsences'],
			'StaffAttendances' 	=> ['className' => 'Institution.StaffAttendances', 'actions' => ['index']],
			'StaffBehaviours' 	=> ['className' => 'Institution.StaffBehaviours'],
			'StaffPositions' 	=> ['className' => 'Institution.StaffPositions'],

			'Students' 			=> ['className' => 'Institution.Students'],
			'StudentUser' 		=> ['className' => 'Institution.StudentUser', 'actions' => ['add', 'view', 'edit']],
			'StudentAbsences' 	=> ['className' => 'Institution.InstitutionSiteStudentAbsences'],
			'StudentAttendances'=> ['className' => 'Institution.StudentAttendances', 'actions' => ['index']],
			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'Assessments' 		=> ['className' => 'Institution.InstitutionAssessments', 'actions' => ['index', 'view', 'edit', 'remove']],
			'Results' 			=> ['className' => 'Institution.InstitutionAssessmentResults', 'actions' => ['index']],
			'Promotion' 		=> ['className' => 'Institution.StudentPromotion', 'actions' => ['index']],
			'TransferApprovals' => ['className' => 'Institution.TransferApprovals', 'actions' => ['edit', 'view']],
			'TransferRequests' 	=> ['className' => 'Institution.TransferRequests', 'actions' => ['add', 'edit', 'remove']],
			'DropoutApprovals' => ['className' => 'Institution.DropoutApprovals', 'actions' => ['index', 'edit', 'view']],
			'DropoutRequests' 	=> ['className' => 'Institution.DropoutRequests', 'actions' => ['add', 'edit', 'remove']],
			'StudentAdmission'	=> ['className' => 'Institution.StudentAdmission', 'actions' => ['index', 'edit', 'view']],

			'BankAccounts' 		=> ['className' => 'Institution.InstitutionSiteBankAccounts'],
			'Fees' 				=> ['className' => 'Institution.InstitutionSiteFees'],
			'StudentFees' 		=> ['className' => 'Institution.StudentFees', 'actions' => ['index', 'view', 'edit']],

			// Surveys
			'Surveys' 			=> ['className' => 'Institution.InstitutionSurveys', 'actions' => ['index', 'view', 'edit', 'remove']],

			// Quality
			'Rubrics' 			=> ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
			'RubricAnswers' 	=> ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],
			'Visits' 			=> ['className' => 'Institution.InstitutionQualityVisits']
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
				if ($action == 'dashboard') {
					$session->write('Institution.Institutions.id', $id);
				}
				$this->activeObj = $this->Institutions->get($id);
				$name = $this->activeObj->name;
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
				$this->Navigation->addCrumb($model->getHeader($alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $alias]);
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

			if ($model->hasField('institution_id')) {
				$model->fields['institution_id']['type'] = 'hidden';
				$model->fields['institution_id']['value'] = $session->read('Institution.Institutions.id');
			}

			if ($model->hasField('institution_site_id') && !is_null($this->activeObj)) {
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('Institution.Institutions.id');
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
						return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $alias]);
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
			} else if ($model->hasField('institution_site_id')) { // will need to remove this part once we change institution_sites to institutions
				if (!$session->check('Institution.Institutions.id')) {
					$this->Alert->error('general.notExists');
					// should redirect
				} else {
					$query->where([$model->aliasField('institution_site_id') => $session->read('Institution.Institutions.id')]);
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
			$InstitutionSiteStudents = TableRegistry::get('Institution.Students');
			$highChartDatas[] = $InstitutionSiteStudents->getHighChart('number_of_students_by_year', $params);
			
			//Students By Grade for current year
			$params = array(
				'conditions' => array('institution_id' => $id)
			);

			$highChartDatas[] = $InstitutionSiteStudents->getHighChart('number_of_students_by_grade', $params);

			//Staffs By Position for current year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$InstitutionSiteStaff = TableRegistry::get('Institution.Staff');
			$highChartDatas[] = $InstitutionSiteStaff->getHighChart('number_of_staff', $params);

			$this->set('highChartDatas', $highChartDatas);

		} else {
			return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}
	}

}
