<?php

namespace Education\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class EducationsController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);

        if($this->request->action != 'CopySystems'){
            $selectedAction = $this->request->action;
            $setupTab = 'Stages';
            if (in_array($selectedAction, ['Stages', 'Subjects', 'Certifications', 'ProgrammeOrientations', 'FieldOfStudies'])) {
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
                'GradeSubjects' => [
                    'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'GradeSubjects'],
                    'text' => __('Grade Subjects')
                ],
                $setupTab => [
                    'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $setupTab],
                    'text' => __('Setup')
                ]
            ];
            $tabElements = $this->TabPermission->checkTabPermission($tabElements);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', $selectedAction);
        }
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Education');
        //POCOR-5696 start
        if($this->request->action == 'CopySystems'){
            $model->alias = 'Systems';
            $header .= ' - ' . $model->getHeader('SystemsCopy');
        }else{
            $header .= ' - ' . $model->getHeader($model->alias);
        }
        //POCOR-5696 ends
        $this->Navigation->addCrumb('Education Structure', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function Subjects() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationSubjects']);
    }

    public function Certifications() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationCertifications']);
    }

    public function FieldOfStudies() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationFieldOfStudies']);
    }

    public function ProgrammeOrientations() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationProgrammeOrientations']);
    }

    public function Systems() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationSystems']);
    }
    //POCOR-5696 start
    public function CopySystems() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationSystems']);
    }
    //POCOR-5696 ends
    public function Levels() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationLevels']);
    }

    public function Cycles() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationCycles']);
    }

    public function Programmes() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationProgrammes']);
    }

    public function Grades() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationGrades']);
    }

    public function GradeSubjects() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationGradesSubjects']);
    }

    public function Stages() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Education.EducationStages']);
    }

}
