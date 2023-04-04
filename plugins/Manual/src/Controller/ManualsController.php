<?php
namespace Manual\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ManualsController extends AppController
{
    public function initialize()
    {
        parent::initialize();        
        $this->loadComponent('Paginator');
    }

    // public function StudentMarkTypes()
    // {
    //     $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Attendance.StudentMarkTypes']);
    // }

    // public function StudentMarkTypeStatuses()
    // {
    //     $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Attendance.StudentMarkTypeStatuses']);
    // }


    public function Institutions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Institution']);
    }

    public function Directory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Directory']);
    }
    public function Reports()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Reports']);
    }
    public function Administration()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Administration']);
    }
    public function Personal()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Personal']);
    }
    public function Guardian()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manual.Guardian']);
    }


    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $selectedAction = $this->request->action;
        
        if ($selectedAction == 'Institutions') {
            $setupTab = 'Institutions';
        } else if ($selectedAction == 'Directory') {
            $setupTab = 'Directory';
        } else if ($selectedAction == 'Reports') {
            $setupTab = 'Reports';
        } else if ($selectedAction == 'Administration') {
            $setupTab = 'Administration';
        } else if ($selectedAction == 'Personal') {
            $setupTab = 'Personal';
        } else if ($selectedAction == 'Guardian') {
            $setupTab = 'Guardian';
        }
        $tabElements = [
            'Institutions' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Institutions'],
                'text' => __('Institutions')
            ],
            'Directory' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Directory'],
                'text' => __('Directory')
            ],
            'Reports' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Reports'],
                'text' => __('Reports')
            ],
            'Administration' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Administration'],
                'text' => __('Administration')
            ],
            'Personal' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Personal'],
                'text' => __('Personal')
            ],
            'Guardian' => [
                'url' => ['plugin' => 'Manual', 'controller' => 'Manuals', 'action' => 'Guardian'],
                'text' => __('Guardian')
            ],
            

        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $setupTab);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {

        if ($model->alias == 'Institutions') {
            $header = 'Institutions';
        } else if ($model->alias == 'Directory') {
            $header = 'Directory';
        } else if ($model->alias == 'Reports') {
            $header = 'Reports';
        } else if ($model->alias == 'Administration') {
            $header = 'Administration';
        } else if ($model->alias == 'Personal') {
            $header = 'Personal';
        } else if ($model->alias == 'Guardian') {
            $header = 'Guardian';
        }
        
        $this->Navigation->addCrumb('System Configuration', ['plugin' => 'Configuration', 'controller' => 'Configurations', 'action' =>'index']);
        $this->Navigation->addCrumb('Manuals');

        $this->set('contentHeader', $header);
    }
}
