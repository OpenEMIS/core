<?php
namespace Institution\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class InstitutionsController extends AppController
{

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.Institutions');
		$this->ControllerAction->models = [
			'Attachments' => ['className' => 'Institution.Attachments'],
			'Additional' => ['className' => 'Institution.Additional'],

			// 'InstitutionSiteCustomField',
			// 'InstitutionSiteCustomFieldOption',


			'Positions' => ['className' => 'Institution.Positions'],
			'Programmes' => ['className' => 'Institution.Programmes'],
			'Shifts' => ['className' => 'Institution.Shifts'],
			'Sections' => ['className' => 'Institution.Sections'],
			'Classes' => ['className' => 'Institution.Classes'],
			'Infrastructures' => ['className' => 'Institution.Infrastructures'],

			'StudentAbsences' => ['className' => 'Institution.StudentAbsences'],
			'StaffAbsences' => ['className' => 'Institution.StaffAbsences'],

			'AssessmentResults' => ['className' => 'Institution.AssessmentResults'],

			'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
			'StaffBehaviours' => ['className' => 'Institution.StaffBehaviours'],

			'BankAccounts' => ['className' => 'Institution.BankAccounts'],
			'Fees' => ['className' => 'Institution.Fees'],
			'StudentFees' => ['className' => 'Institution.StudentFees'],

			// // Surveys
			'NewSurveys' => ['className' => 'Institution.SurveyNew'],
			'DraftedSurveys' => ['className' => 'Institution.SurveyDrafts'],
			'CompletedSurveys' => ['className' => 'Institution.SurveyCompleted'],

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
			// $session->delete('InstitutionSites.id');
			// $this->InstitutionSites->fields['alternative_name']['visible']['index'] = false;
			// $this->InstitutionSites->fields['address']['visible']['index'] = false;
			// $this->InstitutionSites->fields['postal_code']['visible']['index'] = false;
			// $this->InstitutionSites->fields['telephone']['visible']['index'] = false;
			// $this->InstitutionSites->fields['fax']['visible']['index'] = false;
			// $this->InstitutionSites->fields['email']['visible']['index'] = false;
			// $this->InstitutionSites->fields['website']['visible']['index'] = false;
			// $this->InstitutionSites->fields['date_opened']['visible']['index'] = false;
			// $this->InstitutionSites->fields['year_opened']['visible']['index'] = false;
			// $this->InstitutionSites->fields['date_closed']['visible']['index'] = false;
			// $this->InstitutionSites->fields['year_closed']['visible']['index'] = false;
			// $this->InstitutionSites->fields['longitude']['visible']['index'] = false;
			// $this->InstitutionSites->fields['latitude']['visible']['index'] = false;
			// $this->InstitutionSites->fields['security_group_id']['visible']['index'] = false;
			// $this->InstitutionSites->fields['contact_person']['visible']['index'] = false;
			// $this->InstitutionSites->fields['institution_site_area_id']['visible']['index'] = false;
		} elseif ($action == 'edit') {

			// $this->InstitutionSites->fields['year_opened']['visible']['edit'] = false;
			// $this->InstitutionSites->fields['year_closed']['visible']['edit'] = false;			
			// $this->InstitutionSites->fields['security_group_id']['visible']['edit'] = false;
			// $this->InstitutionSites->fields['institution_site_area_id']['visible']['edit'] = false;

			// $this->InstitutionSites->fields['area_id']['type'] = 'select';
			// $list = $this->InstitutionSites->Areas->find('list');
			// $list = $list->toArray();
			// $this->InstitutionSites->fields['area_id']['options'] = $list;

			// $this->InstitutionSites->fields['area_administrative_id']['type'] = 'select';
			// $list = $this->InstitutionSites->AreaAdministratives->find('list');
			// $list = $list->toArray();
			// $this->InstitutionSites->fields['area_administrative_id']['options'] = $list;

			// $this->InstitutionSites->fields['institution_site_locality_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_locality_id']['options'] = $this->InstitutionSites->InstitutionSiteLocalities->getList();

			// $this->InstitutionSites->fields['institution_site_type_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_type_id']['options'] = $this->InstitutionSites->InstitutionSiteTypes->getList();

			// $this->InstitutionSites->fields['institution_site_ownership_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_ownership_id']['options'] = $this->InstitutionSites->InstitutionSiteOwnerships->getList();

			// $this->InstitutionSites->fields['institution_site_status_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_status_id']['options'] = $this->InstitutionSites->InstitutionSiteStatuses->getList();

			// $this->InstitutionSites->fields['institution_site_sector_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_sector_id']['options'] = $this->InstitutionSites->InstitutionSiteSectors->getList();

			// $this->InstitutionSites->fields['institution_site_provider_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_provider_id']['options'] = $this->InstitutionSites->InstitutionSiteProviders->getList();

			// $this->InstitutionSites->fields['institution_site_gender_id']['type'] = 'select';
			// $this->InstitutionSites->fields['institution_site_gender_id']['options'] = $this->InstitutionSites->InstitutionSiteGenders->getList();

		} elseif ($action == 'view') {
			// $this->InstitutionSites->fields['year_opened']['visible']['view'] = false;
			// $this->InstitutionSites->fields['year_closed']['visible']['view'] = false;			
			// $this->InstitutionSites->fields['security_group_id']['visible']['view'] = false;
			// $this->InstitutionSites->fields['institution_site_area_id']['visible']['view'] = false;			
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
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($session, $controller, $header) {
			$header .= ' - ' . $model->getHeader($model->alias);
			$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);

			if (array_key_exists('institution_site_id', $model->fields)) {
				if (!$session->check('Institutions.id')) {
					$this->Message->alert('general.notExists');
				}
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('Institutions.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) use ($session) {
			if (array_key_exists('institution_site_id', $model->fields)) {
				if (!$session->check('Institutions.id')) {
					$this->Message->alert('general.notExists');
				}
				$options['conditions'][] = ['Institutions.id' => $session->read('Institutions.id')];
			}
			
			return $options;
		};

		$this->set('contentHeader', $header);
    }
}
