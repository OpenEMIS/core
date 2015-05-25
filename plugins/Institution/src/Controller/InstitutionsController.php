<?php
namespace Institution\Controller;

use Institution\Controller\AppController;
use Cake\Event\Event;

class InstitutionsController extends AppController
{

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.InstitutionSites');
		$this->ControllerAction->models = [
			'Attachments' => ['className' => 'Institution.InstitutionSiteAttachments']
		];
		$this->loadComponent('Paginator');
		
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Institution');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;
			$session = $this->request->session();

			if (array_key_exists('institution_site_id', $model->fields)) {
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = 1;//$session->read('InstitutionSite.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);

		if ($this->request->action = 'index') {
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
    }
}
