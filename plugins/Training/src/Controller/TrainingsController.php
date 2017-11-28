<?php
namespace Training\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class TrainingsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
        $this->loadComponent('Training.Training');

        $this->ControllerAction->models = [
            'ImportTrainees'    => ['className' => 'Training.ImportTrainees', 'actions' => ['add']],
        ];
    }

    // CAv4
    public function Courses() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Training.TrainingCourses']); }
    public function Sessions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Training.TrainingSessions']); }
    public function Applications() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Training.TrainingApplications']); }
    public function Results() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Training.TrainingSessionResults']); }
    // End

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Training');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Training', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function getSessionTabElements($options = [])
    {
        $tabElements = [];
        $sessionUrl = ['plugin' => 'Training', 'controller' => 'Trainings'];
        $sessionTabElements = [
            'Sessions' => ['text' => __('Sessions')],
            'Applications' => ['text' => __('Applications')]
        ];

        $tabElements = array_merge($tabElements, $sessionTabElements);

        foreach ($sessionTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($sessionUrl, ['action' => $key, 'index']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
