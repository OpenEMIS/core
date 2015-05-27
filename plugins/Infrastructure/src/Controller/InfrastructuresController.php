<?php
namespace Infrastructure\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
    private $parentId = 0;
    private $selectedSecondLevel = null;
    private $selectedThirdLevel = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Infrastructure.InfrastructureLevels');
		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes'],
			'CustomFields' => ['className' => 'Infrastructure.InfrastructureCustomFields']
		];
		$this->loadComponent('Paginator');
		
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Infrastructure');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;
			$session = $this->request->session();

			if (array_key_exists('infrastructure_level_id', $model->fields)) {
				if (!$session->check('Levels.id')) {
					$this->Message->alert('general.notExists');
				}
				$model->fields['infrastructure_level_id']['type'] = 'hidden';
				$model->fields['infrastructure_level_id']['value'] = $session->read('Levels.id');
			}
			
			$controller->set('contentHeader', $header);
		};

		// $this->ControllerAction->beforePaginate = function($model, $options) {
		// 	// logic here
		// 	return $options;
		// };

		$this->set('contentHeader', $header);

        $this->parentId = ($this->request->query('parent_id')) ? $this->request->query('parent_id') : 0;

		if ($this->request->action = 'index') {

            if ($this->ControllerAction->model()->alias()=='InfrastructureLevels') {

	            $this->ControllerAction->beforePaginate = function($model, $options) {
                    $options['conditions'][] = [
                        $model->alias().'.parent_id' => $this->parentId
                    ];

	                return $options;
	            };

	            // $this->set('selectedSecondLevelKey', 'level');
	            // $this->set('selectedSecondLevel', $this->selectedSecondLevel);
	            // $this->set('secondLevelOptions', $secondLevelOptions);
	        }

		} else if ($this->request->action = 'view') {
			
			// $this->Areas->fields['modified_user_id']['visible'] = false;
			// $this->Areas->fields['created_user_id']['visible'] = false;
			// $this->Areas->fields['modified']['visible'] = false;
			// $this->Areas->fields['created']['visible'] = false;

		} else if ($this->request->action = 'edit') {

			// $this->Areas->fields['modified_user_id']['visible'] = false;
			// $this->Areas->fields['created_user_id']['visible'] = false;
			// $this->Areas->fields['modified']['visible'] = false;
			// $this->Areas->fields['created']['visible'] = false;

		}

    }

    public function beforeRender(Event $event) {
		if ($this->request->action = 'index') {

			$toolbarElements = [];
            if ($this->ControllerAction->model()->alias()=='InfrastructureLevels') {
	            $toolbarElements = [
	                ['name' => 'nav_tabs', 'data' => [], 'options' => []],
	                ['name' => 'breadcrumbs', 'data' => [], 'options' => []]
	            ];

	           
	            // $this->set('selectedSecondLevelKey', 'level');
	            // $this->set('selectedSecondLevel', $this->selectedSecondLevel);
	            // $this->set('secondLevelOptions', $secondLevelOptions);
	        } elseif ($this->ControllerAction->model()->alias()=='InfrastructureTypes') {
	            $toolbarElements = [
	                ['name' => 'controls', 'data' => [], 'options' => []]
	            ];

	            $list = $this->InfrastructureLevels->getList();
	            $this->selectedSecondLevel = ($this->request->query('level')) ? $this->request->query('level') : key($list);
				$secondLevelOptions = [];
	            foreach ($list as $key => $value) {
	                $secondLevelOptions['level=' . $key] = $value;
	            }

	            
	            $this->set('selectedSecondLevelKey', 'level');
	            $this->set('selectedSecondLevel', $this->selectedSecondLevel);
	            $this->set('secondLevelOptions', $secondLevelOptions);
	        } elseif ($this->ControllerAction->model()->alias()=='InfrastructureCustomFields') {

	        }

            $this->set('toolbarElements', $toolbarElements);

		} else if ($this->request->action = 'view') {
			
			// $this->Areas->fields['modified_user_id']['visible'] = false;
			// $this->Areas->fields['created_user_id']['visible'] = false;
			// $this->Areas->fields['modified']['visible'] = false;
			// $this->Areas->fields['created']['visible'] = false;

		} else if ($this->request->action = 'edit') {

			// $this->Areas->fields['modified_user_id']['visible'] = false;
			// $this->Areas->fields['created_user_id']['visible'] = false;
			// $this->Areas->fields['modified']['visible'] = false;
			// $this->Areas->fields['created']['visible'] = false;

		}

    }

}
