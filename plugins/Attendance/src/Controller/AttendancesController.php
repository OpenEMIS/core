<?php
namespace Attendance\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AttendancesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function StudentMarkTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Attendance.StudentMarkTypes']);
    }

    public function StudentMarkTypeStatuses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Attendance.StudentMarkTypeStatuses']);
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event) {

        if ($this->getPlugin() == 'Attendance') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
        $selectedAction = $this->request->getParam('action');

        if ($selectedAction == 'StudentMarkTypes') {
            $setupTab = 'Attendances';
        } else if ($selectedAction == 'StudentMarkTypeStatuses') {
            $setupTab = 'Status';
        }
        $tabElements = [
            'Attendances' => [
                'url' => ['plugin' => 'Attendance', 'controller' => 'Attendances', 'action' => 'StudentMarkTypes'],
                'text' => __('Attendances')
            ],
            'Status' => [
                'url' => ['plugin' => 'Attendance', 'controller' => 'Attendances', 'action' => 'StudentMarkTypeStatuses'],
                'text' => __('Status')
            ]
        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $setupTab);
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        if ($model->alias == 'StudentMarkTypes') {
            $header = 'Attendances';
        } else if ($model->alias == 'StudentMarkTypeStatuses') {
            $header = 'Status';
        }

        $this->Navigation->addCrumb('Attendances', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function beforeRender(Event|\Cake\Event\EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
