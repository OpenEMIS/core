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
    	$this->Navigation->addCrumb('Education', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $this->request->action]);
		$this->Navigation->addCrumb($this->request->action);

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

		$tabElements = [
			'Systems' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems'],
				'text' => __('Systems')
			],
			'Levels' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Levels'],
				'text' => __('Levels')
			],
			'Cycles' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Cycles'],
				'text' => __('Cycles')
			],
			'Programmes' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Programmes'],
				'text' => __('Programmes')
			],
			'Grades' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Grades'],
				'text' => __('Grades')
			],
			'Subjects' => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Subjects'],
				'text' => __('Subjects')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
