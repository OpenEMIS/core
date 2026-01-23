<?php
namespace Rubric\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;

class RubricsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->models = [
            //'Sections' => ['className' => 'Rubric.RubricSections'],
            'Criterias' => ['className' => 'Rubric.RubricCriterias'],
            'Options' => ['className' => 'Rubric.RubricTemplateOptions'],
            'Status' => ['className' => 'Rubric.RubricStatuses'],
            'RubricCriterias' => ['className' => 'Rubric.RubricCriterias'],
            'RubricTemplateOptions' => ['className' => 'Rubric.RubricTemplateOptions'],
            'RubricStatuses' => ['className' => 'Rubric.RubricStatuses']
        ];
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Templates()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Rubric.RubricTemplates']);
    }

    public function Sections()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Rubric.RubricSections']);
    }

    // end CAv4

    public function beforeFilter(EventInterface $event)
    {
        $serverRequest = $this->request;
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
        if (!is_null($serverRequest->getQuery('template'))) {
            $template = $serverRequest->getQuery('template');
            foreach ($tabElements as $key => $obj) {
                $tabElements[$key]['url']['template'] = $template;
            }
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->getParam('action'));
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Rubric');

        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Rubric', ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => $model->getAlias()]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

        $this->set('contentHeader', $header);
    }

    public function beforePaginate(EventInterface $event, Table $model, Query $query, ArrayObject $options)
    {
        if ($model->getAlias() == 'Sections' || $model->getAlias() == 'Criterias' || $model->getAlias() == 'Options') {
            $request = $this->request;

            $RubricTemplates = TableRegistry::getTableLocator()->get('Rubric.RubricTemplates');
            $templateOptions = $RubricTemplates
                ->find('list')
                ->toArray();
            $selectedTemplate = !is_null($request->getQuery('template')) ? $request->getQuery('template') : key($templateOptions);

            $columns = $model->getSchema()->columns();
            if (in_array('rubric_section_id', $columns)) {
                $RubricSections = TableRegistry::getTableLocator()->get('Rubric.RubricSections');
                $sectionOptions = $RubricSections
                    ->find('list')
                    ->find('order')
                    ->where([$RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
                    ->toArray();
                $selectedSection = !is_null($request->getQuery('section')) ? $request->getQuery('section') : key($sectionOptions);

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
