<?php
namespace Training\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class TrainingCoursesController extends AppController
{
    public function initialize() {
        parent::initialize();

        $this->ControllerAction->model('Training.TrainingCourses');
        $this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = __('Training');

        $header .= ' - ' . __('Courses');
        $this->Navigation->addCrumb('Training', ['plugin' => 'Training', 'controller' => 'TrainingCourses', 'action' => 'index']);
        $this->Navigation->addCrumb('Courses');

        $this->set('contentHeader', $header);
    }
}
