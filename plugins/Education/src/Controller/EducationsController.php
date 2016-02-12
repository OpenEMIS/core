<?php
namespace Education\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class EducationsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Systems' => ['className' => 'Education.EducationSystems', 'options' => ['deleteStrategy' => 'transfer']],
			'Levels' => ['className' => 'Education.EducationLevels', 'options' => ['deleteStrategy' => 'transfer']],
			'Cycles' => ['className' => 'Education.EducationCycles', 'options' => ['deleteStrategy' => 'transfer']],
			'Programmes' => ['className' => 'Education.EducationProgrammes', 'options' => ['deleteStrategy' => 'transfer']],
			'Grades' => ['className' => 'Education.EducationGrades', 'options' => ['deleteStrategy' => 'transfer']],
			'Subjects' => ['className' => 'Education.EducationSubjects'],
			'Certifications' => ['className' => 'Education.EducationCertifications'],
			'FieldOfStudies' => ['className' => 'Education.EducationFieldOfStudies'],
			'ProgrammeOrientations' => ['className' => 'Education.EducationProgrammeOrientations']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$selectedAction = $this->request->action;
		$setupTab = 'Subjects';
		if (in_array($selectedAction, ['Subjects', 'Certifications', 'ProgrammeOrientations', 'FieldOfStudies'])) {
			$setupTab = $selectedAction;
		}

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
			$setupTab => [
				'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $setupTab],
				'text' => __('Setup')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $selectedAction);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Education');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Education Structure', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
