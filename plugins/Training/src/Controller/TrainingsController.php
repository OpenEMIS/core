<?php
namespace Training\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class TrainingsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Paginator');
        $this->loadComponent('Training.Training');
        $this->loadModel('Training.TrainingSessionTraineeResults');//5695
        $this->ControllerAction->models = [
            'ImportTrainees'    => ['className' => 'Training.ImportTrainees', 'actions' => ['add']],
            'ImportTrainingSessionTraineeResults' => ['className' => 'Training.ImportTrainingSessionTraineeResults', 'actions' => ['add']] //5695
        ];
        $this->loadComponent('RequestHandler');

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

        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Training', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->getAlias()]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

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

    public function beforeRender(Event|\Cake\Event\EventInterface $event)
    {
        parent::beforeRender($event);

        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event)
    {

        if ($this->getPlugin() == 'Training') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
    }

}
