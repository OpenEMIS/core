<?php
namespace AcademicPeriod\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class AcademicPeriodsController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Levels' => ['className' => 'AcademicPeriod.AcademicPeriodLevels'],
            'Periods' => ['className' => 'AcademicPeriod.AcademicPeriods']
        ];
        $this->loadComponent('Paginator');
    }

    public function Levels()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'AcademicPeriod.AcademicPeriodLevels']);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $tabElements = [
            'Levels' => [
                'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Levels'],
                'text' => __('Levels')
            ],
            'Periods' => [
                'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods'],
                'text' => __('Periods')
            ]
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Academic Period');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Academic Period', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
