<?php
namespace StaffAppraisal\Controller;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use App\Controller\AppController;
use Cake\Http\ServerRequest;

class StaffAppraisalsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        //$this->loadComponent('FormProtection');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $header = 'Appraisals';
        $request = $this->request;
        $this->Navigation->addCrumb($header, ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Criterias']);
        $this->Navigation->addCrumb(Inflector::humanize(isset($request->getAttribute('params')['action'])? $request->getAttribute('params')['action']: ''));
        $this->getAppraisalsTabElements();
        $this->set('contentHeader', __($header));
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Appraisals');
        $header .= ' - ' . __($model->getHeader($model->alias));
        $this->set('contentHeader', $header);
    }

    private function getAppraisalsTabElements()
    {
        $request = $this->request;
        $plugin = $this->getPlugin();
        $name = $this->getName();
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
        $this->set('selectedAction',$request->getAttribute('params')['action']);
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
