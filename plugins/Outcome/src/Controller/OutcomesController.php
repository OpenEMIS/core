<?php
namespace Outcome\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;

use App\Controller\AppController;

class OutcomesController extends AppController
{
    // CAv4
    public function Templates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Outcome.OutcomeTemplates']); }
    public function Criterias() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Outcome.OutcomeCriterias']); }
    public function Periods() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Outcome.OutcomePeriods']); }
    public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Outcome.OutcomeGradingTypes']); }
    // End

    public function getOutcomeTabs()
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

    public function getOutcomeTemplateTabs($params = [])
    {
        $tabElements = [
            'Templates' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates', 0 => 'view'],
                'text' => __('Overview')
            ],
            'Criterias' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Criterias'],
                'text' => __('Criterias')
            ]
        ];

        $queryString = $this->paramsDecode($params['queryString']);
        if (isset($queryString['outcome_template_id']) && isset($queryString['academic_period_id'])) {
            $tabElements['Templates']['url'][1] = $this->paramsEncode(['id' => $queryString['outcome_template_id'], 'academic_period_id' => $queryString['academic_period_id']]);
        }

        foreach ($tabElements as $key => $value) {
            // set querystring to tab urls
            $tabElements[$key]['url'] = array_merge($value['url'], $params);
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Outcome');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->set('contentHeader', $header);

        $this->Navigation->addCrumb('Outcomes', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));
    }
}
