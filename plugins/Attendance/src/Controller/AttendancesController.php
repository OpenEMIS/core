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

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Attendances');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Attendances', ['plugin' => 'Education', 'controller' => 'Educations', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function StudentMarkTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Attendance.StudentMarkTypes']);
    }
}
