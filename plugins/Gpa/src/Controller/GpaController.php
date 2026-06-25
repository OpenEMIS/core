<?php

namespace Gpa\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class GpaController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        if ($this->getPlugin() == 'Gpa') {
            $this->Security->setConfig('validatePost', false);
        }

        $Tabname = $this->request->getParam('action');
        $header = $this->splitOnCapitalLetters($Tabname);
        $action = $this->request->getParam('action');
        //POCOR-9160 start
        $header = trim($header);
        if ($header == "Gpa System") {
            $header = 'GPA System';
        }else if ($header == "Cumulative") {
            $header = 'Cumulative GPA';
        } else if ($header == "Gpa Grading Type") {
            $header = 'GPA Grading Types';
        }
        //POCOR-9160 end
        //$header .= ' - '.__(Inflector::humanize($action));
        $this->Navigation->addCrumb($header, ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $action]);
        $this->set('contentHeader', $header);
    }
    private function splitOnCapitalLetters($string)
    {

        $words = preg_split('/(?=[A-Z])/', $string);

        return implode(' ', $words);
    }

    public function getGpaTab($action = null)
    {
        $tabElements = [
            'Gpa' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'GpaSystem'],
                'text' => __('GPA')
            ],
            'Cumulative' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Cumulative'],
                'text' => __('Cumulative GPA')
            ],
            'GpaGradingType' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'GpaGradingType'],
                'text' => __('Grading Types')
            ]
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $action = !is_null($action) ? $action : $this->request->getParam('action');
        if ($action == 'GpaSystem') {
            $action = 'Gpa';
        } else {
            $action = $action;
        }
        $this->set('selectedAction', $action);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }

    public function GpaSystem()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Gpa.GpaSystem']);
    }

    public function Cumulative()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Gpa.Cumulative']);
    }
    public function GpaGradingType()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Gpa.GpaGradingTypes']);
    }
}
