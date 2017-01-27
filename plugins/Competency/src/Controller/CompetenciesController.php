<?php
namespace Competency\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class CompetenciesController extends AppController
{
    public function initialize() {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Templates()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.Templates']); }
    public function Items()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.Items']); }
    public function Criterias()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.Criterias']); }
    public function Periods()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.Periods']); }
    public function GradingTypes()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.GradingTypes']); }
    // End

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);

        $tabElements = [
            'Templates' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates'],
                'text' => __('Templates')
            ],
            'Items' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Items'],
                'text' => __('Items')
            ],
            'Criterias' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Criterias'],
                'text' => __('Criterias')
            ],
            'Periods' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Periods'],
                'text' => __('Periods')
            ],
            'GradingTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
                'text' => __('Criteria Grading Types')
            ],
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);

        // if ($this->request->action=='addNewAssessmentPeriod') {
        //  $this->request->params['_ext'] = 'json';
        // }

    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Competency');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Competencies', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
