<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class CompetencyPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('competency_periods');

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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
                ])
            ->requirePresence('competency_items', 'create');
            // ->add('start_date', 'ruleCompareDate', [
            //     'rule' => ['compareDate', 'end_date', true]
            // ])
            // ->add('date_enabled', 'ruleCompareDate', [
            //     'rule' => ['compareDate', 'date_disabled', true]
            // ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getCompetencyTabs();
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $serverRequest = new ServerRequest();
        $request = $this->request;

        //academic period filter
        $extra['selectedPeriod'] = !empty($request->getQuery('period')) ? $request->getQuery('period') : $this->AcademicPeriods->getCurrent();
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

        if ($request->getQuery('template')) {
            $selectedTemplate = $request->getQuery('template');
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

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Periods','Competencies');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedPeriod'])) {
            if ($extra['selectedPeriod']) {
                $conditions[$this->aliasField('academic_period_id')] = $extra['selectedPeriod'];
            }
        }

        if (isset($extra['selectedTemplate'])) {
            if ($extra['selectedTemplate']) {
                $conditions[$this->aliasField('competency_template_id')] = $extra['selectedTemplate'];
            }
        }

        $query->where([$conditions]);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->request->getQuery('queryString') && !$this->request->getQuery('period') && !$this->request->getQuery('template')) {
            $queryString = $this->getQueryString();
            $this->request->data[$this->getAlias()]['academic_period_id'] = $queryString['academic_period_id'];
            $this->request->data[$this->getAlias()]['competency_template_id'] = $queryString['competency_template_id'];
        } else {
            if ($this->request->getQuery('period')) {
                $this->request->data[$this->getAlias()]['academic_period_id'] = $this->request->getQuery('period');
            }
            if ($this->request->getQuery('template')) {
                $this->request->data[$this->getAlias()]['competency_template_id'] = $this->request->getQuery('template');
            }
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'CompetencyItems.Criterias','Templates'
        ]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('competency_items', [
            'type' => 'element',
            'element' => 'Competency.competency_items'
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_items', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //massage data to match many to many data format.
        if (array_key_exists($this->getAlias(), (array)$requestData)) {
            if (array_key_exists('competency_items', $requestData[$this->getAlias()])) {
                if (is_array($requestData[$this->getAlias()]['competency_items']['_ids'])) {
                    foreach ($requestData[$this->getAlias()]['competency_items']['_ids'] as $key => $item) {
                        $requestData[$this->getAlias()]['competency_items'][$key]['id'] = $requestData[$this->getAlias()]['competency_items']['_ids'][$key];
                        $requestData[$this->getAlias()]['competency_items'][$key]['academic_period_id'] = $requestData[$this->getAlias()]['academic_period_id'];
                        $requestData[$this->getAlias()]['competency_items'][$key]['competency_template_id'] = $requestData[$this->getAlias()]['competency_template_id'];
                        $requestData[$this->getAlias()]['competency_items'][$key]['_joinData']['competency_item_id'] = $requestData[$this->getAlias()]['competency_items']['_ids'][$key];
                        $requestData[$this->getAlias()]['competency_items'][$key]['_joinData']['academic_period_id'] = $requestData[$this->getAlias()]['academic_period_id'];
                        $requestData[$this->getAlias()]['competency_items'][$key]['_joinData']['competency_template_id'] = $requestData[$this->getAlias()]['competency_template_id'];
                    }
                }
                unset($requestData[$this->getAlias()]['competency_items']['_ids']);
            }
        }
        $newOptions = ['associated' => ['CompetencyItems']]; //so during patch entity, it can get the necessary datas
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);

    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['default'] = !empty($this->request->getQuery('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
                if (!$request->getData($this->aliasField('academic_period_id'))) {
                    $request->getData[$this->getAlias()]['academic_period_id'] = $attr['default'];
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

    public function addEditOnChangeAcademicPeriod(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['template'] = '-1';
        $request->getQuery['item'] = '-1';
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData)) {
                if (array_key_exists('academic_period_id', $request->getData[$this->alias()])) {
                    $request->query['period'] = $request->getData[$this->getAlias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $selectedPeriod = $request->getData($this->aliasField('academic_period_id'));
            if(!empty($selectedPeriod)){
                $selectedPeriod = $request->getData($this->aliasField('academic_period_id'));
            }else{
                $selectedPeriod = $this->AcademicPeriods->getCurrent();
            }
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

    public function addEditOnChangeCompetencyTemplate(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['item'] = '-1';
        $requestData = $request->getData();
        if ($request->is(['post', 'put'])) {
            if (isset($requestData[$this->getAlias()])) {
                if (isset($requestData[$this->getAlias()]['academic_period_id'])) {
                    $request->getQuery['period'] = $requestData[$this->getAlias()]['academic_period_id'];
                }

                if (isset($requestData[$this->getAlias()]['competency_template_id'])) {
                    $request->getQuery['template'] = $requestData[$this->getAlias()]['competency_template_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyItems(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // POCOR-9056 start
            $entity = $attr['entity'];
                $selectedTemplateId = $entity->competency_template_id;
                $selectedPeriodId = $entity->academic_period_id;
                $itemOptions = [];
                if ($selectedTemplateId && $selectedPeriodId) {
                    $itemOptions = $this->CompetencyItems->find('ItemList',
                        ['templateId' => $selectedTemplateId,
                            'academicPeriodId' => $selectedPeriodId])
                        ->toArray();
                }
                $attr['options'] = $itemOptions;
        }
        // POCOR-9056 end
        return $attr;
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->CompetencyItems->getAlias(),
        ];
    }
    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'competency_template_id') {
            return __('Competency Template');
        } elseif ($field == 'competency_items') {
            return __('Competency Items');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'start_date') {
            return __('Start Date');
        } elseif ($field == 'end_date') {
            return __('End Date');
        } elseif ($field == 'date_enabled') {
            return __('Date Enabled');
        } elseif ($field == 'date_disabled') {
            return __('Date Disabled');
        }  elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
