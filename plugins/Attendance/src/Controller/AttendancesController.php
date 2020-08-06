<?php
namespace Attendance\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AttendancesController extends AppController
{
    public function initialize()
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

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
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
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Attendances');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Attendances', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
