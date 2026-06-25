<?php

namespace Competency\Controller;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class CompetenciesController extends AppController
{
    // CAv4
    public function Templates()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyTemplates']);
    }

    public function Items()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyItems']);
    }

    public function Criterias()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyCriterias']);
    }

    public function Periods()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyPeriods']);
    }

    public function GradingTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyGradingTypes']);
    }

    // End
    public function initialize(): void
    {
        parent::initialize();
        $this->ControllerAction->models = [
            'ImportCompetencyTemplates' => ['className' => 'Competency.ImportCompetencyTemplates', 'actions' => ['add']]
        ];
    }

    public function getCompetencyTabs()
    {
        $tabElements = [
            'Templates' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'Templates'],
                'text' => __('Templates')
            ],
            'Periods' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'Periods'],
                'text' => __('Periods')
            ],
            'GradingTypes' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'GradingTypes'],
                'text' => __('Grading Types')
            ],
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $action = $this->getRequest()->getParam('action'); //POCOR-8074-5
        $this->set('selectedAction', $action); //POCOR-8074-5
    }

    public function getCompetencyTemplateTabs()
    {
        //POCOR-8074-5 start: query string having template id and academic period id
        $decodedQueryString = $this->getQueryString();
        $queryString = $this->paramsEncode($decodedQueryString);
        $competency_template_id = $decodedQueryString['competency_template_id'];
        $academic_period_id = $decodedQueryString['academic_period_id'];
        if (!isset($competency_template_id) || !isset($academic_period_id)) {
            //die(print_r($decodedQueryString, true));
        }
        $tabElements = [
            'Templates' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'Templates',
                    0 => 'view',
                    1 => $queryString],
                'text' => __('Overview')
            ],
            'Items' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'Items',
                    'queryString' => $queryString],
                'text' => __('Items')
            ],
            'Criterias' => [
                'url' => [
                    'plugin' => $this->getPlugin(),
                    'controller' => $this->getName(),
                    'action' => 'Criterias',
                    'queryString' => $queryString],
                'text' => __('Criterias')
            ]
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $action = $this->getRequest()->getParam('action'); //POCOR-8074-5
        $this->set('selectedAction', $action); //POCOR-8074-5
        //POCOR-8074-5 end
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Competency');
        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Competencies', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->getAlias()]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

        $this->set('contentHeader', $header);
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event)
    {
        if ($this->getPlugin() == 'Competency') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);

    }


}
