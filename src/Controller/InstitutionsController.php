<?php
namespace App\Controller;
use Cake\Event\Event;

class InstitutionsController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('InstitutionSites');
		$this->loadComponent('Paginator');

		$this->set('contentHeader', 'Institutions');
    }

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

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

		// columns to be removed, used by ECE QA Dashboard
		$this->InstitutionSites->fields['institution_site_area_id']['visible'] = $visibility;
	}
}
