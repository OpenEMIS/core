<?php
namespace Education\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class EducationsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Systems' => ['className' => 'Education.EducationSystems'],
			'Levels' => ['className' => 'Education.EducationLevels'],
			'Cycles' => ['className' => 'Education.EducationCycles'],
			'Programmes' => ['className' => 'Education.EducationProgrammes'],
			'Grades' => ['className' => 'Education.EducationGrades'],
			'Subjects' => ['className' => 'Education.EducationSubjects']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Education');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;

			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);
	}
}
