<?php
namespace Area\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class AreasController extends AppController
{

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Area.Areas');
		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Area.AreaLevels'],
			'Administratives' => ['className' => 'Area.AreaAdministratives'],
			'AdministrativeLevels' => ['className' => 'Area.AreaAdministrativeLevels']
		];
		$this->loadComponent('Paginator');
		
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Area');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;
			$session = $this->request->session();

			if (array_key_exists('area_id', $model->fields)) {
				if (!$session->check('Areas.id')) {
					$this->Message->alert('general.notExists');
				}
				$model->fields['area_id']['type'] = 'hidden';
				$model->fields['area_id']['value'] = $session->read('Areas.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);

		if ($this->request->action = 'index') {

			$this->Areas->fields['parent_id']['visible'] = false;
			$this->Areas->fields['lft']['visible'] = false;
			$this->Areas->fields['rght']['visible'] = false;
			$this->Areas->fields['order']['visible'] = false;

			$this->Areas->fields['modified_user_id']['visible'] = false;
			$this->Areas->fields['created_user_id']['visible'] = false;
			$this->Areas->fields['modified']['visible'] = false;
			$this->Areas->fields['created']['visible'] = false;

		} else if ($this->request->action = 'view') {
			
			// $this->Areas->fields['modified_user_id']['visible'] = false;
			// $this->Areas->fields['created_user_id']['visible'] = false;
			// $this->Areas->fields['modified']['visible'] = false;
			// $this->Areas->fields['created']['visible'] = false;

		} else if ($this->request->action = 'edit') {

			$this->Areas->fields['modified_user_id']['visible'] = false;
			$this->Areas->fields['created_user_id']['visible'] = false;
			$this->Areas->fields['modified']['visible'] = false;
			$this->Areas->fields['created']['visible'] = false;

		}

    }
}
