<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class CompetencyPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('competency_periods');

        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        // $this->belongsTo('Items',           ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('Templates',       ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);

        $this->belongsToMany('CompetencyItems', [
            'className' => 'Competency.CompetencyItems',
            'joinTable' => 'competency_items_periods',
            'foreignKey' => ['competency_period_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'targetForeignKey' => ['competency_item_id', 'academic_period_id', 'competency_template_id'],
            'through' => 'Competency.CompetencyItemsPeriods',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('CompetencyPeriodComments', ['className' => 'Institution.InstitutionCompetencyPeriodComments', 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('CompetencyItemComments', ['className' => 'Institution.InstitutionCompetencyItemComments', 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('competency_items', 'create')
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
            ->add('date_enabled', 'ruleCompareDate', [
                'rule' => ['compareDate', 'date_disabled', true]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getCompetencyTabs();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        $extra['selectedPeriod'] = !empty($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $data['periodOptions'] = $this->AcademicPeriods->getYearList();
        $data['selectedPeriod'] = $extra['selectedPeriod'];

        //template filter
        $templateOptions = $this->Templates
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([$this->Templates->aliasField('academic_period_id') => $extra['selectedPeriod']])
                    ->toArray();

        if ($templateOptions) {
            $templateOptions = ['0' => '-- '.__('All Templates').' --'] + $templateOptions;
        }

        if ($request->query('template')) {
            $selectedTemplate = $request->query('template');
        } else {
            $selectedTemplate = 0;
        }

        $extra['selectedTemplate'] = $selectedTemplate;
        $data['templateOptions'] = $templateOptions;
        $data['selectedTemplate'] = $selectedTemplate;

        $data['baseUrl'] = $this->url('index');

        $extra['elements']['control'] = [
            'name' => 'Competency.periods_controls',
            'data' => $data,
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[$this->aliasField('academic_period_id')] = $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedTemplate', $extra)) {
            if ($extra['selectedTemplate']) {
                $conditions[$this->aliasField('competency_template_id')] = $extra['selectedTemplate'];
            }
        }

        $query->where([$conditions]);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->request->query('queryString') && !$this->request->query('period') && !$this->request->query('template')) {
            $queryString = $this->getQueryString();
            $this->request->data[$this->alias()]['academic_period_id'] = $queryString['academic_period_id'];
            $this->request->data[$this->alias()]['competency_template_id'] = $queryString['competency_template_id'];
        } else {
            if ($this->request->query('period')) {
                $this->request->data[$this->alias()]['academic_period_id'] = $this->request->query('period');
            }
            if ($this->request->query('template')) {
                $this->request->data[$this->alias()]['competency_template_id'] = $this->request->query('template');
            }
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'CompetencyItems.Criterias','Templates'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('competency_items', [
            'type' => 'element',
            'element' => 'Competency.competency_items'
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_items', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        // Added required flag as a workaround to show asterix
        $this->field('competency_items', [
            'type' => 'chosenSelect',
            'entity' => $entity,
            'attr' => ['required' => true]
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_items', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //massage data to match many to many data format.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('competency_items', $requestData[$this->alias()])) {
                if (is_array($requestData[$this->alias()]['competency_items']['_ids'])) {
                    foreach ($requestData[$this->alias()]['competency_items']['_ids'] as $key => $item) {
                        $requestData[$this->alias()]['competency_items'][$key]['id'] = $requestData[$this->alias()]['competency_items']['_ids'][$key];
                        $requestData[$this->alias()]['competency_items'][$key]['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                        $requestData[$this->alias()]['competency_items'][$key]['competency_template_id'] = $requestData[$this->alias()]['competency_template_id'];
                        $requestData[$this->alias()]['competency_items'][$key]['_joinData']['competency_item_id'] = $requestData[$this->alias()]['competency_items']['_ids'][$key];
                        $requestData[$this->alias()]['competency_items'][$key]['_joinData']['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                        $requestData[$this->alias()]['competency_items'][$key]['_joinData']['competency_template_id'] = $requestData[$this->alias()]['competency_template_id'];
                    }
                }
                unset($requestData[$this->alias()]['competency_items']['_ids']);
            }
        }
        $newOptions = ['associated' => ['CompetencyItems']]; //so during patch entity, it can get the necessary datas
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);

    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['default'] = !empty($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
                if (!$request->data($this->aliasField('academic_period_id'))) {
                    $request->data[$this->alias()]['academic_period_id'] = $attr['default'];
                }
                $attr['options'] = $this->AcademicPeriods->getYearList();
                $attr['onChangeReload'] = 'changeAcademicPeriod';
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get([$attr['entity']->academic_period_id])->name;
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['template'] = '-1';
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedPeriod = $request->data($this->aliasField('academic_period_id'));
            $templateOptions = [];

            if ($selectedPeriod) {
                $templateOptions = $this->Templates
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([$this->Templates->aliasField('academic_period_id') => $selectedPeriod])
                    ->toArray();
            }

            $attr['options'] = $templateOptions;
            $attr['onChangeReload'] = 'changeCompetencyTemplate';

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->competency_template_id;
            $attr['attr']['value'] = $attr['entity']->template->code_name;

        }
        return $attr;
    }

    public function addEditOnChangeCompetencyTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('competency_template_id', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['competency_template_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyItems(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                $selectedTemplate = $request->data($this->aliasField('competency_template_id'));
                $selectedPeriod = $request->data($this->aliasField('academic_period_id'));
                $itemOptions = [];
                if ($selectedTemplate && $selectedPeriod) {
                    $itemOptions = $this->CompetencyItems->find('ItemList', ['templateId' => $selectedTemplate, 'academicPeriodId' => $selectedPeriod])->toArray();
                }

                $attr['options'] = $itemOptions;

            } else {
                $attr['type'] = 'element';
                $attr['element'] = 'Competency.competency_items';
            }
        }
        return $attr;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->CompetencyItems->alias(),
        ];
    }
}
