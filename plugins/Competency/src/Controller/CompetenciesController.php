<?php
namespace Competency\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class CompetenciesController extends AppController
{
    // CAv4
    public function Templates()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyTemplates']); }
    public function Items()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyItems']); }
    public function Criterias()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyCriterias']); }
    public function Periods()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyPeriods']); }
    public function GradingTypes()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Competency.CompetencyGradingTypes']); }
    // End
     public function initialize()
    {       
        parent::initialize();
        $this->ControllerAction->models = [
            'ImportCompetencyTemplates'   => ['className' => 'Competency.ImportCompetencyTemplates', 'actions' => ['add']]
        ];
    }
    public function getCompetencyTabs()
    {
        $tabElements = [
            'Templates' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates'],
                'text' => __('Templates')
            ],
            'Periods' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Periods'],
                'text' => __('Periods')
            ],
            'GradingTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
                'text' => __('Grading Types')
            ],
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function getCompetencyTemplateTabs($params = [])
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
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
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
