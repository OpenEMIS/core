<?php
namespace Training\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class TrainingSessionsController extends AppController
{
    public function initialize() {
        parent::initialize();

        $this->ControllerAction->model('Training.TrainingSessions');
        $this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = __('Training');

        $header .= ' - ' . __('Sessions');
        $this->Navigation->addCrumb('Training', ['plugin' => 'Training', 'controller' => 'TrainingSessions', 'action' => 'index']);
        $this->Navigation->addCrumb('Sessions');

        $this->set('contentHeader', $header);
    }
}
