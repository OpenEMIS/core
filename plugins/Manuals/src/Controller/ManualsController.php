<?php
namespace Manuals\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ManualsController extends AppController
{
    public function initialize(): void
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
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Institution']);
    }

    public function Directory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Directory']);
    }
    public function Reports()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Reports']);
    }
    public function Administration()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Administration']);
    }
    public function Personal()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Personal']);
    }
    public function Guardian()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Manuals.Guardian']);
    }


    public function beforeFilter(EventInterface $event) {
        parent::beforeFilter($event);
        $selectedAction = $this->request->getParam('action');
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
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Institutions'],
                'text' => __('Institutions')
            ],
            'Directory' => [
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Directory'],
                'text' => __('Directory')
            ],
            'Reports' => [
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Reports'],
                'text' => __('Reports')
            ],
            'Administration' => [
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Administration'],
                'text' => __('Administration')
            ],
            'Personal' => [
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Personal'],
                'text' => __('Personal')
            ],
            'Guardian' => [
                'url' => ['plugin' => 'Manuals', 'controller' => 'Manuals', 'action' => 'Guardian'],
                'text' => __('Guardian')
            ],


        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $setupTab);
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {

        if ($model->getAlias() == 'Institutions') {
            $header = 'Institutions';
        } else if ($model->getAlias() == 'Directory') {
            $header = 'Directory';
        } else if ($model->getAlias() == 'Reports') {
            $header = 'Reports';
        } else if ($model->getAlias() == 'Administration') {
            $header = 'Administration';
        } else if ($model->getAlias() == 'Personal') {
            $header = 'Personal';
        } else if ($model->getAlias() == 'Guardian') {
            $header = 'Guardian';
        }

        $this->Navigation->addCrumb('System Configuration', ['plugin' => 'Configuration', 'controller' => 'Configurations', 'action' =>'index']);
        $this->Navigation->addCrumb('Manuals');

        $this->set('contentHeader', $header);
    }
}
