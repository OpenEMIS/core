<?php
namespace Rubric\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RubricsController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Sections' => ['className' => 'Rubric.RubricSections'],
            'Criterias' => ['className' => 'Rubric.RubricCriterias'],
            'Options' => ['className' => 'Rubric.RubricTemplateOptions'],
            'Status' => ['className' => 'Rubric.RubricStatuses']
        ];
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Templates()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Rubric.RubricTemplates']);
    }
    // end CAv4

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $tabElements = [
            'Templates' => [
                'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Templates'],
                'text' => __('Templates')
            ],
            'Sections' => [
                'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Sections'],
                'text' => __('Sections')
            ],
            'Criterias' => [
                'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Criterias'],
                'text' => __('Criterias')
            ],
            'Options' => [
                'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Options'],
                'text' => __('Options')
            ],
            'Status' => [
                'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Status'],
                'text' => __('Status')
            ]
        ];

        // pass query string for selected template across tabs
        if (!is_null($this->request->query('template'))) {
            $template = $this->request->query('template');
            foreach ($tabElements as $key => $obj) {
                $tabElements[$key]['url']['template'] = $template;
            }
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Rubric');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Rubric', ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        if ($model->alias == 'Sections' || $model->alias == 'Criterias' || $model->alias == 'Options') {
            $request = $this->request;

            $RubricTemplates = TableRegistry::get('Rubric.RubricTemplates');
            $templateOptions = $RubricTemplates
                ->find('list')
                ->toArray();
            $selectedTemplate = !is_null($request->query('template')) ? $request->query('template') : key($templateOptions);

            $columns = $model->schema()->columns();
            if (in_array('rubric_section_id', $columns)) {
                $RubricSections = TableRegistry::get('Rubric.RubricSections');
                $sectionOptions = $RubricSections
                    ->find('list')
                    ->find('order')
                    ->where([$RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
                    ->toArray();
                $selectedSection = !is_null($request->query('section')) ? $request->query('section') : key($sectionOptions);

                $query->where([$model->aliasField('rubric_section_id') => $selectedSection]);

                $this->set(compact('sectionOptions', 'selectedSection'));
            } else {
                $options['conditions'][] = [
                    $model->aliasField('rubric_template_id') => $selectedTemplate
                ];
            }

            $this->set(compact('templateOptions', 'selectedTemplate'));
        }
    }
}
