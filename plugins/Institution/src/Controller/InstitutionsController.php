<?php
namespace Institution\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class InstitutionsController extends AppController
{

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.InstitutionSites');
		$this->ControllerAction->models = [
			'Attachments' => ['className' => 'Institution.InstitutionSiteAttachments'],
			'Additional' => ['className' => 'Institution.InstitutionSiteAdditionals'],

			// 'InstitutionSiteCustomField',
			// 'InstitutionSiteCustomFieldOption',


			'Positions' => ['className' => 'Institution.InstitutionSitePositions'],
			'Programmes' => ['className' => 'Institution.InstitutionSiteProgrammes'],
			'Shifts' => ['className' => 'Institution.InstitutionSiteShifts'],
			'Sections' => ['className' => 'Institution.InstitutionSiteSections'],
			'Classes' => ['className' => 'Institution.InstitutionSiteClasses'],
			'Infrastructures' => ['className' => 'Institution.InstitutionSiteInfrastructures'],

			'StudentAbsences' => ['className' => 'Institution.InstitutionSiteStudentAbsences'],
			'StaffAbsences' => ['className' => 'Institution.InstitutionSiteStaffAbsences'],

			'AssessmentResults' => ['className' => 'Institution.InstitutionSiteAssessmentResults'],

			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'StaffBehaviours' => ['className' => 'Institution.StaffBehaviours'],

			'BankAccounts' => ['className' => 'Institution.InstitutionSiteBankAccounts'],
			'Fees' => ['className' => 'Institution.InstitutionSiteFees'],
			'StudentFees' => ['className' => 'Institution.StudentFees'],

			// Surveys
			// 'Fees' => ['className' => 'Institution.InstitutionSiteFees'],
			// 'Fees' => ['className' => 'Institution.InstitutionSiteFees'],
			// 'Fees' => ['className' => 'Institution.InstitutionSiteFees'],

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
			$session->delete('InstitutionSites.id');
		}

		if ($session->check('InstitutionSites.id') || $action == 'view') {
    		$id = 0;
    		if ($session->check('InstitutionSites.id')) {
    			$id = $session->read('InstitutionSites.id');
    		} else if (isset($this->request->pass[0])) {
    			$id = $this->request->pass[0];
    		}
    		if (!empty($id)) {
    			$obj = $this->InstitutionSites->get($id);
	    		$name = $obj->name;
	    		$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]);
    		} else {
    			return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
    		}
    	}

    	$header = __('Institution');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($session, $controller, $header) {
			$header .= ' - ' . $model->getHeader($model->alias);
			$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);

			if (array_key_exists('institution_site_id', $model->fields)) {
				if (!$session->check('InstitutionSites.id')) {
					$this->Message->alert('general.notExists');
				}
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('InstitutionSites.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) use ($session) {
			if (array_key_exists('institution_site_id', $model->fields)) {
				if (!$session->check('InstitutionSites.id')) {
					$this->Message->alert('general.notExists');
				}
				$options['conditions'][] = ['InstitutionSites.id' => $session->read('InstitutionSites.id')];
			}
			
			return $options;
		};

		$this->set('contentHeader', $header);
    }
}
