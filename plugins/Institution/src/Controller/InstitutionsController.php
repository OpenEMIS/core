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

			$model->fields['institution_site_id']['type'] = 'hidden';
			$model->fields['institution_site_id']['value'] = 1;//$session->read('InstitutionSite.id');
			$controller->set('contentHeader', $header);
		};
		$this->set('contentHeader', $header);

    	$visibility = ['view' => true, 'edit' => true];

    	$this->InstitutionSites->fields['alternative_name']['visible'] = $visibility;
		$this->InstitutionSites->fields['address']['visible'] = $visibility;
		$this->InstitutionSites->fields['postal_code']['visible'] = $visibility;
		$this->InstitutionSites->fields['telephone']['visible'] = $visibility;
		$this->InstitutionSites->fields['fax']['visible'] = $visibility;
		$this->InstitutionSites->fields['email']['visible'] = $visibility;
		$this->InstitutionSites->fields['website']['visible'] = $visibility;
		$this->InstitutionSites->fields['date_opened']['visible'] = $visibility;
		$this->InstitutionSites->fields['year_opened']['visible'] = $visibility;
		$this->InstitutionSites->fields['date_closed']['visible'] = $visibility;
		$this->InstitutionSites->fields['year_closed']['visible'] = $visibility;
		$this->InstitutionSites->fields['longitude']['visible'] = $visibility;
		$this->InstitutionSites->fields['latitude']['visible'] = $visibility;
		$this->InstitutionSites->fields['security_group_id']['visible'] = $visibility;
		$this->InstitutionSites->fields['contact_person']['visible'] = $visibility;
    }
}
