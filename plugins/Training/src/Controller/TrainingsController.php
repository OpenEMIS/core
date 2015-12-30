<?php
namespace Training\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class TrainingsController extends AppController
{
    public function initialize() {
        parent::initialize();
        $this->ControllerAction->models = [
            'Courses' => ['className' => 'Training.TrainingCourses'],
            'Sessions' => ['className' => 'Training.TrainingSessions'],
            'Results' => ['className' => 'Training.TrainingSessionResults', 'actions' => ['index', 'view', 'edit', 'remove']]
        ];
        $this->loadComponent('Paginator');
        $this->loadComponent('Training.Training');
    }

    public function onInitialize(Event $event, Table $model) {
        $header = __('Training');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Training', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
