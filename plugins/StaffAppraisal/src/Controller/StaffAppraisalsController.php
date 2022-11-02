<?php
namespace StaffAppraisal\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use App\Controller\AppController;

class StaffAppraisalsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Appraisals';
        $this->Navigation->addCrumb($header, ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Criterias']);
        $this->Navigation->addCrumb(Inflector::humanize($this->request->action));
        $this->getAppraisalsTabElements();
        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Appraisals');
        $header .= ' - ' . __($model->getHeader($model->alias));
        $this->set('contentHeader', $header);
    }

    private function getAppraisalsTabElements()
    {
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [
            'Criterias' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Criterias'],
                'text' => __('Criterias')
            ],
            'Forms' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Forms'],
                'text' => __('Forms')
            ],
            'Types' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Types'],
                'text' => __('Types')
            ],
            // Added
            'Scores' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Scores'],
                'text' => __('Scores')
            ],
            'Periods' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Periods'],
                'text' => __('Periods')
            ]
        ];

        $this->set('tabElements', $this->TabPermission->checkTabPermission($tabElements));
        $this->set('selectedAction', $this->request->param('action'));
    }

    public function Criterias()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function Forms()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalForms']);
    }

    public function Types()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalTypes']);
    }

    // Added
    public function Scores()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalScores']);
    }

    public function Periods()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalPeriods']);
    }
}
