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

    public function getCompetencyTabs($params = [])
    {
        $tabElements = [
            'Templates' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates', 0 => 'view'],
                'text' => __('Overview')
            ],
            'Items' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Items'],
                'text' => __('Items')
            ],
            'Criterias' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Criterias'],
                'text' => __('Criterias')
            ]
        ];
        $queryString = $this->ControllerAction->getQueryString();
        if (isset($queryString['competency_template_id']) && isset($queryString['academic_period_id'])) {
            $tabElements['Templates']['url'][1] = $this->ControllerAction->paramsEncode(['id' => $queryString['competency_template_id'], 'academic_period_id' => $queryString['academic_period_id']]);
        }

        foreach ($tabElements as $key => $value) {
            $tabElements[$key]['url'] = array_merge($value['url'], $params);
        }

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Competency');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Competencies', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
