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
			$this->InstitutionSites->fields['alternative_name']['visible']['index'] = false;
			$this->InstitutionSites->fields['address']['visible']['index'] = false;
			$this->InstitutionSites->fields['postal_code']['visible']['index'] = false;
			$this->InstitutionSites->fields['telephone']['visible']['index'] = false;
			$this->InstitutionSites->fields['fax']['visible']['index'] = false;
			$this->InstitutionSites->fields['email']['visible']['index'] = false;
			$this->InstitutionSites->fields['website']['visible']['index'] = false;
			$this->InstitutionSites->fields['date_opened']['visible']['index'] = false;
			$this->InstitutionSites->fields['year_opened']['visible']['index'] = false;
			$this->InstitutionSites->fields['date_closed']['visible']['index'] = false;
			$this->InstitutionSites->fields['year_closed']['visible']['index'] = false;
			$this->InstitutionSites->fields['longitude']['visible']['index'] = false;
			$this->InstitutionSites->fields['latitude']['visible']['index'] = false;
			$this->InstitutionSites->fields['security_group_id']['visible']['index'] = false;
			$this->InstitutionSites->fields['contact_person']['visible']['index'] = false;
		}

		if ($session->check('InstitutionSites.id') || $action == 'view') {
    		$id = $session->check('InstitutionSites.id') ? $session->read('InstitutionSites.id') : $this->request->pass[0];
    		$obj = $this->InstitutionSites->get($id);
    		$name = $obj->name;
    		$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]);
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
