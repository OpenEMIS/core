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
				if (!$session->check('InstitutionSites.id')) {
					$this->Message->alert('general.notExists');
				}
				$model->fields['institution_site_id']['type'] = 'hidden';
				$model->fields['institution_site_id']['value'] = $session->read('InstitutionSites.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			$session = $this->request->session();
			if (array_key_exists('institution_site_id', $model->fields)) {
				if (!$session->check('InstitutionSites.id')) {
					$this->Message->alert('general.notExists');
				}
				$options['conditions'][] = ['InstitutionSites.id' => $session->read('InstitutionSites.id')];
			}
			return $options;
		};

		$this->set('contentHeader', $header);

		if ($this->request->action = 'index') {

			// pr($this->InstitutionSites->InstitutionSiteAttachments->fields());
			// $this->InstitutionSiteAttachments->fields['modified_user_id']['visible'] = false;
			// // $this->InstitutionSites->InstitutionSiteAttachments->fields['created_user_id']['visible'] = false;
			// $this->InstitutionSites->InstitutionSiteAttachments->fields['modified']['visible'] = false;
			// $this->InstitutionSites->InstitutionSiteAttachments->fields['created']['visible'] = false;

			$this->InstitutionSites->fields['modified_user_id']['visible'] = false;
			$this->InstitutionSites->fields['created_user_id']['visible'] = false;
			$this->InstitutionSites->fields['modified']['visible'] = false;
			$this->InstitutionSites->fields['created']['visible'] = false;


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

		} else if ($this->request->action = 'view') {
			
			$this->InstitutionSites->fields['modified_user_id']['visible'] = false;
			$this->InstitutionSites->fields['created_user_id']['visible'] = false;
			$this->InstitutionSites->fields['modified']['visible'] = false;
			$this->InstitutionSites->fields['created']['visible'] = false;

		}
    }
}
