<?php
namespace Configuration\Model\Table; //POCOR-9257

use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Cake\Http\Session;

class WebhooksTable extends ControllerActionTable //POCOR-9257
{
    use OptionsTrait;
    const ACTIVE = 1;
    const INACTIVE = 0;

    const SUPPORTED_METHOD = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => 'DELETE'
    ];

    const OPEN_EMIS_EXAMS = 'OpenEMIS Exams';
    const OPEN_EMIS_CORE  = 'OpenEMIS Core';

    const EXCLUDED_FIELDS = [
        'password',
        // 'security_group_id',
        'super_admin',
        '_content'
    ];

    private $eventKeyOptions = [
        'logout' => 'Logout',
        'institutions_create' => 'Institution Create',
        'class_create'        => 'Class Create',
        'class_update'        => 'Class Update',
        'subject_create'      => 'Subject Create',
        'student_create'      => 'Student Create',
        'student_update'      => 'Student Update',
        'subject_update'      => 'Subject Update',
        'staff_create'        => 'Staff Create',
        'staff_update'        => 'Staff Update',
        'institutions_update' => 'Institution Update',
        'institutions_delete' => 'Institutions Delete',
        'programme_create'    => 'Programme Create',
        'programme_update'    => 'Programme Update',
        'programme_delete'    => 'Programme Delete',
        'class_delete'        => 'Class Delete',
        'subject_delete'      => 'Subject Delete',
        'student_delete'      => 'Student Delete',
        'staff_delete'        => 'Staff Delete',
        'security_user_delete' => 'Delete Security User',
        'academic_period_create' => 'Academic Period Create',
        'academic_period_update' => 'Academic Period Update',
        'academic_period_delete' => 'Academic Period Delete',
        'education_cycle_create' => 'Education Structure Cycle Create',
        'education_cycle_update' => 'Education Structure Cycle Update',
        'education_cycle_delete' => 'Education Structure Cycle Delete',
        'education_programme_create' => 'Education Programme Create',
        'education_programme_update' => 'Education Programme Update',
        'education_programme_delete' => 'Education Programme Delete',
        'education_grade_create' => 'Education Grade Create',
        'education_grade_update' => 'Education Grade Update',
        'education_grade_delete' => 'Education Grade Delete',
        'education_subject_create' => 'Education Subject Create',
        'education_subject_update' => 'Education Subject Update',
        'education_subject_delete' => 'Education Subject Delete',
        'education_grade_subject_create' => 'Education Grade Subject Create',
        'education_grade_subject_update' => 'Education Grade Subject Update',
        'education_grade_subject_delete' => 'Education Grade Subject Delete',
        'area_education_create' => 'Area Education Create',
        'area_education_update' => 'Area Education Update',
        'area_education_delete' => 'Area Education Delete',
        'education_level_create' => 'Education Structure Level Create',
        'education_level_update' => 'Education Structure Level Update',
        'education_level_delete' => 'Education Structure Level Delete',
        'role_update'           => 'Role Update',
        'education_structure_system_update' => 'Education Structure System Update',
        'role_create'           => 'Role Create',
        'role_delete'           => 'Role Delete',
        'education_structure_system_delete' => 'Education Structure System Delete',
        'attendance_update' => 'Student Attendance Update',
    ];


    public function initialize(array $config): void
    {
        $this->setTable('webhooks');
        parent::initialize($config);
//        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
        //POCOR-9257: ConfigItems behavior intentionally NOT added — this controller is fully independent
        $this->addBehavior('OpenEmis.Section');

        foreach ($this->eventKeyOptions as $key => $value) {
            $this->eventKeyOptions[$key] = __($value);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->requirePresence('url')
            ->notEmptyString('url', __('This field cannot be left empty'))
            ->notEmptyString('event_key', __('This field cannot be left empty'))
            ->notEmptyString('name', __('This field cannot be left empty'))
            ->requirePresence('external_data_source_id') //POCOR-9257: external server is required
            ->notEmptyString('external_data_source_id', __('This field cannot be left empty'))
            ;
        return $validator;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
//            ->contain(['WebhookEvents'])
        ;

        //POCOR-9257: start - apply index filter conditions
        $eventKey = $this->request->getQuery('event_key');
        if (!empty($eventKey) && $eventKey !== 'all') {
            $query->where([$this->aliasField('event_key') => $eventKey]);
        }

        $status = $this->request->getQuery('status');
        if (isset($status) && $status !== '' && $status !== 'all') {
            $query->where([$this->aliasField('status') => $status]);
        }

        $method = $this->request->getQuery('method');
        if (!empty($method) && $method !== 'all') {
            $query->where([$this->aliasField('method') => $method]);
        }

        $externalSourceId = $this->request->getQuery('external_data_source_id');
        if (!empty($externalSourceId) && $externalSourceId !== 'all') {
            $query->where([$this->aliasField('external_data_source_id') => $externalSourceId]);
        }
        //POCOR-9257: end

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration','Webhooks','System Configurations');
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

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
//            ->contain(['WebhookEvents'])
        ;
    }

//    public function editOnInitialize(Event $event, Entity $entity)
//    {
//        $this->request->getData($this->getAlias())['triggered_event']['_ids'] = [];
//        foreach ($entity->webhook_events as $event) {
//            $this->request->getData($this->getAlias())['triggered_event']['_ids'][] = $event->event_key;
//        }
//    }

    //POCOR-9257: implementedEvents() override removed — no ConfigItemsBehavior priority workaround needed

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $supportedMethod = self::SUPPORTED_METHOD;
        $this->fields['description']['visible']['index'] = false;
        $this->field('name');
//        $this->field('url', ['type' => 'string']);
        $this->field('external_data_source_id', ['type' => 'hidden', 'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true]]);
        $this->field('external_data_source', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);


        $this->field('external_data_source', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('status', ['options' => $this->getSelectOptions('general.active')]);
        $this->field('method', ['options' => $supportedMethod]);
    }

    public function onGetTemplatePlaceholdersElement(EventInterface  $event, $action, $entity, $attr, $options = [])
    {
        if (!in_array($action, ['edit', 'add'])) {
            return;
        }

        $eventKey = $entity->event_key ?? null;
        $placeholders = [];

        if ($eventKey) {
            $eventDef = self::getEvents()[$eventKey] ?? [];

            // Step 1: Use explicit placeholders if available
            $placeholders = $eventDef['placeholders'] ?? [];

            // Step 2: Fallback to model schema if no placeholders
            if (!empty($eventDef['model'])) {
                try {
                    $table = TableRegistry::getTableLocator()->get($eventDef['model']);
                    $columns = $table->getSchema()->columns();
                    $excluded = $eventDef['excluded'] ?? self::EXCLUDED_FIELDS;

                    foreach ($columns as $column) {
                        if (!in_array($column, $excluded, true)) {
                            $placeholderName = Inflector::humanize(Inflector::underscore($column));
                            $autoPlaceholder = '${' . $column . '}';

                            // Keep user-defined placeholders, add missing ones
                            if (!in_array($autoPlaceholder, $placeholders, true)
                                && !array_key_exists($placeholderName, $placeholders)) {
                                $placeholders[$placeholderName] = $autoPlaceholder;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('Error in ' . __FUNCTION__ . ':' . $e->getMessage());
                }
            }
        }

        // Step 3: Build table output
        $tableHeaders = [__('Attribute'), __('Placeholder')];
        $tableCells = [];

        foreach ($placeholders as $attribute => $placeholder) {
            $tableCells[] = [__($attribute), __($placeholder)];
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Webhooks/template_placeholders', ['attr' => $attr]);
    }


    public function onGetExternalDataSource(EventInterface  $event, Entity $entity)
    {
//        dd($event);
        $external_data_source_id = $entity->external_data_source_id;
        if(isset($external_data_source_id) && $external_data_source_id > 0){
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $externalDataOptions = $ConfigItems->find('list')
            ->where(['id' => $external_data_source_id
            ])
            ->toArray();
        return __($externalDataOptions[$external_data_source_id]);
        }
        return [];
    }
    public function onUpdateFieldExternalDataSourceId(EventInterface  $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
            $externalDataOptions = $ConfigItems->find('list')
                ->where(['type' => 'External Data Source - Webhook',
                    'visible' => 1
                ])
                ->toArray();
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $externalDataOptions;

        }

        return $attr;
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
//            Log::debug('Error: ' . $e->getMessage());
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
//    public function addBeforeAction(Event $event, ArrayObject $extra)
//    {
//        $this->field('triggered_event', [
//            'type' => 'chosenSelect',
//            'options' => $this->eventKeyOptions,
//            'before' => 'description',
//            'attr' => ['required' => true]
//        ]);
//    }

//    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
//    {
//           $this->field('url', ['type' => 'string']);
//
//        $this->field('triggered_event', [
//            'before' => 'description'
//        ]);
//    }

//    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
//    {
//        $data['webhook_events'] = [];
//        if (is_array($data['triggered_event']['_ids'])) {
//            foreach ($data['triggered_event']['_ids'] as $event) {
//                $data['webhook_events'][] = ['event_key' => $event];
//            }
//        }
//        $options['associated'] = [
//            'WebhookEvents' => [
//                'validate' => false
//            ]
//        ];
//    }

    public function onUpdateFieldEventKey(EventInterface  $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit'])) {
            $attr['type'] = 'select';
            $attr['options'] = self::getEventSelectOptions();
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onGetEventKey(EventInterface  $event, Entity $entity)
    {
        $eventKey = $entity->event_key ?? null;
        if ($eventKey) {
            return self::getEventLabel($eventKey);
        }

        return '';
    }

    public function setupFields( Entity $entity)
    {
        $this->field('url', ['entity' => $entity]);
        $this->field('event_key', ['type' => 'select', 'after' => 'url']);
        $this->field('webhook_content', ['type' => 'section', 'after' => 'description']);
        $this->field('query_template', ['type' => 'string', 'after' => 'webhook_content']);
        $this->field('body_template', ['type' => 'text', 'after' => 'query_template']);
        $this->field('placeholders', ['type' => 'section', 'after' => 'body_template']);
        $this->field('template_placeholders', [
            'after' => 'placeholders',
            'type' => 'template_placeholders',
            'visible' => [
                'view' => false,
                'edit' => true
            ],
            'valueClass' => 'table-full-width'
        ]);

    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-9257: type_value injection removed — no ConfigItemsBehavior in this table

        $this->field('body_template', ['type' => 'hidden']);
        $this->field('query_template', ['type' => 'hidden']);
        $this->field('url', ['type' => 'string']);
        $this->field('status', ['type' => 'select', 'options' => $this->getSelectOptions('general.active')]);

        $this->setFieldOrder([
            'event_key',
            'name',
            'external_data_source_id',
            'url',
            'status',
            'method']);

        //POCOR-9257: start - register filter controls element for index
        // Only show event keys that have at least one webhook (intersect defined labels with DB values)
        $usedEventKeys = $this->find()
            ->select(['event_key'])
            ->distinct(['event_key'])
            ->all()
            ->extract('event_key')
            ->toArray();
        $usedEventKeyOptions = array_intersect_key($this->eventKeyOptions, array_flip($usedEventKeys));
        $eventKeyOptions = ['all' => __('All Events')] + $usedEventKeyOptions; //POCOR-9257: + preserves string keys
        $selectedEventKey = $this->queryString('event_key', $eventKeyOptions);

        // + operator preserves integer keys (array_merge would renumber 1=>'Active' to 0=>'Active')
        $statusOptions = ['all' => __('All Statuses')] + $this->getSelectOptions('general.active');
        $selectedStatus = $this->queryString('status', $statusOptions);

        $methodOptions = ['all' => __('All Methods')] + self::SUPPORTED_METHOD;
        $selectedMethod = $this->queryString('method', $methodOptions);

        $externalSourceOptions = $this->getUsedExternalSourceOptions();
        $selectedExternalSource = $this->queryString('external_data_source_id', $externalSourceOptions);

        //POCOR-9257: set controls element directly — no ConfigItemsBehavior typeOptions to merge
        $extra['elements']['controls'] = [
            'name'    => 'Configuration.webhook_controls',
            'data'    => [
                'eventKeyOptions'        => $eventKeyOptions,
                'selectedEventKey'       => $selectedEventKey,
                'statusOptions'          => $statusOptions,
                'selectedStatus'         => $selectedStatus,
                'methodOptions'          => $methodOptions,
                'selectedMethod'         => $selectedMethod,
                'externalSourceOptions'  => $externalSourceOptions,
                'selectedExternalSource' => $selectedExternalSource,
            ],
            'options' => [],
            'order'   => 1,
        ];
        //POCOR-9257: end
    }

    //POCOR-9257: returns external source options used by at least one webhook
    private function getUsedExternalSourceOptions(): array
    {
        $usedIds = $this->find()
            ->select(['external_data_source_id'])
            ->where(['external_data_source_id >' => 0])
            ->distinct(['external_data_source_id'])
            ->all()
            ->extract('external_data_source_id')
            ->toArray();

        $options = ['all' => __('All Sources')];
        if (!empty($usedIds)) {
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $sources = $ConfigItems->find('list')
                ->where(['id IN' => $usedIds])
                ->toArray();
            $options = $options + $sources; //POCOR-9257: + preserves integer keys (config_item IDs)
        }
        return $options;
    }

//    public function onGetTriggeredEvent(Event $event, Entity $entity)
//    {
//        $returnString = '';
//        foreach ($entity->webhook_events as $event) {
//            $returnString = $returnString . ', ' . __($this->eventKeyOptions[$event->event_key]);
//        }
//        return ltrim($returnString, ', ');
//    }

//    /**
//     * POCOR-8994
//     *
//     * It retrieves the associated WebhookEvents for the current webhook record
//     using the `id` from the query string
//     * The event keys are then used to pre-select options in the triggered_event chosenSelect dropdown
//     * */
//    public function editBeforeAction(Event $event, ArrayObject $extra)
//    {
//        $queryString = $this->getQueryString();
//        $recordId = $queryString['id'];
//        $webhookEvents = TableRegistry::get('Configuration.WebhookEvents');
//        $record = $webhookEvents->find()
//            ->where([$webhookEvents->aliasField('webhook_id') => $recordId])
//            ->all();
//
//        $storeEvent = [];
//        foreach ($record as $val) {
//            $storeEvent[] = $val['event_key'];
//        }
//        $this->field('triggered_event', [
//                'type' => 'chosenSelect',
//                'options' => $this->eventKeyOptions,
//                'before' => 'description',
//                'attr' => ['required' => true,'value' =>  $storeEvent],
//
//            ]);
//    }

    public function onGetFieldLabel(EventInterface  $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'external_data_source_id') {
            return __('External Server');
        } elseif ($field == 'event_key') {
            return __('Triggered Event');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function addEditAfterAction(EventInterface  $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public static function getEvents(): array
    {
        $deleted_fields = [
            __('Deleted At') => '${deleted_at}',
            __('Deleted By') => '${deleted_by}'
        ];
        return [
            'logout' => [
                'code' => 'logout',
                'label' => 'Logout',
                'model' => null,
                'excluded' => [],
                'placeholders' => [
                    'username' => '${username}',
                    'openemis_no' => '${openemis_no}',
                    'ip' => '${ip}',
                    'logout_time' => '${logout_time}'
                ]
            ],
            'academic_period_create' => [
                'code' => 'academic_period_create',
                'label' => 'Academic Period Create',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'academic_period_delete' => [
                'code' => 'academic_period_delete',
                'label' => 'Academic Period Delete',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'academic_period_update' => [
                'code' => 'academic_period_update',
                'label' => 'Academic Period Update',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'area_education_create' => [
                'code' => 'area_education_create',
                'label' => 'Area Education Create',
                'model' => 'Area.Areas',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'area_education_delete' => [
                'code' => 'area_education_delete',
                'label' => 'Area Education Delete',
                'model' => 'Area.Areas',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'area_education_update' => [
                'code' => 'area_education_update',
                'label' => 'Area Education Update',
                'model' => 'Area.Areas',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_cycle_create' => [
                'code' => 'education_cycle_create',
                'label' => 'Education Cycle Create',
                'model' => 'Education.EducationCycles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_cycle_delete' => [
                'code' => 'education_cycle_delete',
                'label' => 'Education Cycle Delete',
                'model' => 'Education.EducationCycles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_cycle_update' => [
                'code' => 'education_cycle_update',
                'label' => 'Education Cycle Update',
                'model' => 'Education.EducationCycles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_grade_create' => [
                'code' => 'education_grade_create',
                'label' => 'Education Grade Create',
                'model' => 'Education.EducationGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_grade_delete' => [
                'code' => 'education_grade_delete',
                'label' => 'Education Grade Delete',
                'model' => 'Education.EducationGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_grade_update' => [
                'code' => 'education_grade_update',
                'label' => 'Education Grade Update',
                'model' => 'EducationGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_grade_subject_create' => [
                'code' => 'education_grade_subject_create',
                'label' => 'Education Grade Subject Create',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_grade_subject_delete' => [
                'code' => 'education_grade_subject_delete',
                'label' => 'Education Grade Subject Delete',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_grade_subject_update' => [
                'code' => 'education_grade_subject_update',
                'label' => 'Education Grade Subject Update',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_level_create' => [
                'code' => 'education_level_create',
                'label' => 'Education Level Create',
                'model' => 'Education.EducationLevels',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_level_delete' => [
                'code' => 'education_level_delete',
                'label' => 'Education Level Delete',
                'model' => 'Education.EducationLevels',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_level_update' => [
                'code' => 'education_level_update',
                'label' => 'Education Level Update',
                'model' => 'Education.EducationLevels',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_programme_create' => [
                'code' => 'education_programme_create',
                'label' => 'Education Programme Create',
                'model' => 'Education.EducationProgrammes',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_programme_delete' => [
                'code' => 'education_programme_delete',
                'label' => 'Education Programme Delete',
                'model' => 'Education.EducationProgrammes',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_programme_update' => [
                'code' => 'education_programme_update',
                'label' => 'Education Programme Update',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_system_create' => [
                'code' => 'education_system_create',
                'label' => 'Education System Create',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_system_delete' => [
                'code' => 'education_system_delete',
                'label' => 'Education System Delete',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_system_update' => [
                'code' => 'education_system_update',
                'label' => 'Education System Update',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_subject_create' => [
                'code' => 'education_subject_create',
                'label' => 'Education Subject Create',
                'model' => 'Education.EducationSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_subject_delete' => [
                'code' => 'education_subject_delete',
                'label' => 'Education Subject Delete',
                'model' => 'Education.EducationSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'education_subject_update' => [
                'code' => 'education_subject_update',
                'label' => 'Education Subject Update',
                'model' => 'Education.EducationSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_create' => [
                'code' => 'institution_create',
                'label' => 'Institution Create',
                'model' => 'Institution.Institutions',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => ['classification_name']
            ],
            'institution_delete' => [
                'code' => 'institution_delete',
                'label' => 'Institution Delete',
                'model' => 'Institution.Institutions',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'institution_update' => [
                'code' => 'institution_update',
                'label' => 'Institution Update',
                'model' => 'Institution.Institutions',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => ['classification_name']
            ],
            'institution_class_create' => [
                'code' => 'institution_class_create',
                'label' => 'Institution Class Create',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_class_delete' => [
                'code' => 'institution_class_delete',
                'label' => 'Institution Class Delete',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'institution_class_update' => [
                'code' => 'institution_class_update',
                'label' => 'Institution Class Update',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_grade_create' => [
                'code' => 'institution_grade_create',
                'label' => 'Institution Grade Create',
                'model' => 'Institution.InstitutionGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_grade_delete' => [
                'code' => 'institution_grade_delete',
                'label' => 'Institution Grade Delete',
                'model' => 'Institution.InstitutionGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $deleted_fields
            ],
            'institution_grade_update' => [
                'code' => 'institution_grade_update',
                'label' => 'Institution Grade Update',
                'model' => 'Institution.InstitutionGrades',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_subject_create' => [
                'code' => 'institution_subject_create',
                'label' => 'Institution Subject Create',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_subject_delete' => [
                'code' => 'institution_subject_delete',
                'label' => 'Institution Subject Delete',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' =>  $deleted_fields
            ],
            'institution_subject_update' => [
                'code' => 'institution_subject_update',
                'label' => 'Institution Subject Update',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'security_role_create' => [
                'code' => 'security_role_delete',
                'label' => 'Security Role Create',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' =>  $deleted_fields
            ],
            'security_role_delete' => [
                'code' => 'security_role_delete',
                'label' => 'Security Role Delete',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' =>  $deleted_fields
            ],
            'security_role_update' => [
                'code' => 'security_role_update',
                'label' => 'Security Role Update',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' =>  []
            ],
            'security_user_create' => [
                'code' => 'security_user_create',
                'label' => 'Security User Create',
                'model' => 'User.Users',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'security_user_delete' => [
                'code' => 'security_user_delete',
                'label' => 'Security User Delete',
                'model' => 'User.Users',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' =>  $deleted_fields
            ],
            'security_user_update' => [
                'code' => 'security_user_update',
                'label' => 'Security User Update',
                'model' => 'User.Users',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'student_guardian_create' => [
                'code' => 'student_guardian_create',
                'label' => 'Student Guardian Relation Create',
                'model' => 'Student.StudentGuardians',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'student_guardian_delete' => [
                'code' => 'student_guardian_delete',
                'label' => 'Student Guardian Relation Delete',
                'model' => 'Student.StudentGuardians',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' =>  $deleted_fields
            ],
            'student_guardian_update' => [
                'code' => 'student_guardian_update',
                'label' => 'Student Guardian Relation Update',
                'model' => 'Student.StudentGuardians',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],


            'staff_create' => [
                'code' => 'staff_create',
                'label' => 'Staff Create',
                'model' => 'Institution.Staff',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'staff_delete' => [
                'code' => 'staff_delete',
                'label' => 'Staff Delete',
                'model' => 'Institution.Staff',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'staff_update' => [
                'code' => 'staff_update',
                'label' => 'Staff Update',
                'model' => 'Institution.Staff',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],

            'student_create' => [
                'code' => 'student_create',
                'label' => 'Student Create',
                'model' => 'Institution.Students',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'student_delete' => [
                'code' => 'student_delete',
                'label' => 'Student Delete',
                'model' => 'Institution.Students',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'student_update' => [
                'code' => 'student_update',
                'label' => 'Student Update',
                'model' => 'Institution.Students',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'class_student_create' => [
                'code' => 'class_student_create',
                'label' => 'Class Student Create',
                'model' => 'Institution.InstitutionClassStudents',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'class_student_delete' => [
                'code' => 'class_student_delete',
                'label' => 'Class Student Delete',
                'model' => 'Institution.InstitutionClassStudents',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'class_student_update' => [
                'code' => 'class_student_update',
                'label' => 'Class Student Update',
                'model' => 'Institution.InstitutionClassStudents',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
        ];

    }

    public static function getEventLabel(string $eventKey): string
    {
        $events = self::getEvents();
        return $events[$eventKey]['label'] ?? $eventKey;
    }

    public static function getEventSelectOptions(): array
    {
        return collection(self::getEvents())
            ->map(fn($event) => $event['label'])
            ->toArray();
    }

    public static function getPlaceholders(string $eventKey): array
    {
        return self::getEvents()[$eventKey]['placeholders'] ?? [];
    }

    public function triggerCommand(string $eventKey, array $body = []): void
    {
        $configItems = self::getDynamicTableInstance('Configuration.ConfigItems');

        // Fetch ALL active webhook configurations for this event key
        $webhooks = $this->find()
            ->select([
                'id' => $configItems->aliasField('id'),
                'url' => $this->aliasField('url'),
                'query_template' => $this->aliasField('query_template'),
                'body_template' => $this->aliasField('body_template'),
                'method' => $this->aliasField('method'),
                'event_key' => $this->aliasField('event_key'),
                'external_data_webhook_name' => $configItems->aliasField('name')
            ])
            ->innerJoin(
                [$configItems->getAlias() => $configItems->getTable()],
                [$this->aliasField('external_data_source_id') . ' = ' . $configItems->aliasField('id')]
            )
            ->where([
                $this->aliasField('event_key') => trim($eventKey),
                $this->aliasField('status') => self::ACTIVE,
                $configItems->aliasField('value') => self::ACTIVE,
            ])
            ->all();

        if ($webhooks->isEmpty()) {
            Log::write('debug', "No active webhooks found for [$eventKey]");
            return;
        }

        foreach ($webhooks as $webhookConfig) {
            $url = trim($webhookConfig->url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                Log::warning("Invalid URL for webhook [$eventKey]: $url");
                continue;
            }

            $queryTemplate = $webhookConfig->query_template ?? '';
            $bodyTemplate  = $webhookConfig->body_template ?? '';

            // Build final URL
            $url = $this->buildWebhookUrl($url, $queryTemplate, $body);

            // Build body (JSON / template)
            $finalBody = $this->prepareFinalWebhookBody($bodyTemplate, $body);

            // Save JSON body to temp file (if needed)
            $bodyArg = $this->prepareBodyArgument($finalBody);

            // Build shell command
            $cmd = $this->buildWebhookCommand($webhookConfig, $url, $bodyArg);
            $bareCmd = $this->buildBareWebhookCommand($webhookConfig, $url, $bodyArg);

            // Execute asynchronously
            $logs = ROOT . DS . 'logs' . DS . 'webhook.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            $bareShellCmd = $bareCmd . ' ' ;

            try {
                $pid = exec($shellCmd);
                Log::info("Webhook triggered [PID: $pid] URL: $url CMD: $bareShellCmd");
            } catch (\Throwable $ex) {
                Log::error("Exception triggering webhook [$eventKey]: " . $ex->getMessage());
            }
        }
    }

    /**
     * Helper to fill placeholders and build URL
     */
    public function buildWebhookUrl(string $baseUrl, ?string $queryTemplate, array $body): string
    {
        if (empty($queryTemplate)) {
            return $baseUrl;
        }

        foreach ($body as $key => $value) {
            $queryTemplate = str_replace('${' . $key . '}', urlencode((string)$value), $queryTemplate);
        }

        if (strpos($queryTemplate, '?') === 0) {
            $queryParams = ltrim($queryTemplate, '?');
            $baseUrl .= (strpos($baseUrl, '?') === false ? '?' : '&') . $queryParams;
        } else {
            $baseUrl = rtrim($baseUrl, '/') . '/' . ltrim($queryTemplate, '/');
        }

        return $baseUrl;
    }

    /**
     * Helper to build the request body
     */
    public function prepareFinalWebhookBody(?string $template, array $body)
    {
        if (empty($template)) {
            return $body;
        }

        try {
            return $this->interpolateJsonTemplate($template, $body);
        } catch (\Throwable $e) {
            Log::error("Invalid bodyTemplate: " . $e->getMessage());
            return $body;
        }
    }

    /**
     * Helper to determine how to pass the body (file or JSON)
     */
    protected function prepareBodyArgument($finalBody)
    {
        if (is_array($finalBody)) {
            $finalBody = $this->sanitizeWebhookBody($finalBody);
            $temp = TMP . 'webhook_' . uniqid('w', true) . '.json';
            file_put_contents($temp, $this->safeJsonEncode($finalBody));
            return $temp;
        }

        if (is_string($finalBody) && str_ends_with($finalBody, '.json') && file_exists($finalBody)) {
            return $finalBody;
        }

        return json_encode($finalBody);
    }

    /**
     * Helper to assemble the shell command with optional credentials
     */
    protected function buildWebhookCommand($webhookConfig, string $url, string $bodyArg): string
    {

        $escapedBody = escapeshellarg($bodyArg);
        $cmd = ROOT . DS . 'bin' . DS . 'cake webhook ' .
            escapeshellarg($url) . ' ' .
            escapeshellarg(strtolower($webhookConfig->method ?? 'post')) . ' ' .
            $escapedBody;
        // Include credentials if needed
        if (in_array($webhookConfig->external_data_webhook_name, [self::OPEN_EMIS_EXAMS, self::OPEN_EMIS_CORE], true)) {
            $ExternalAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $attributes = $ExternalAttributes->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
                ->where([$ExternalAttributes->aliasField('external_data_source_type')
                => $webhookConfig->id . ':' . $webhookConfig->external_data_webhook_name])
                ->toArray();

            if (!empty($attributes['username'])
                && !empty($attributes['password'])
                ) {
                $serverParams = [
                    'external' => $webhookConfig->external_data_webhook_name,
                    'username' => $attributes['username'],
                    'password' => $attributes['password'],
                    'api_key'  => $attributes['api_key'] ?? '',
                    'api_url'  => $attributes['api_url'] ?? ''
                ];
                $cmd .= ' ' . escapeshellarg(json_encode($serverParams));
            }
        }

        return $cmd;
    }

    protected function buildBareWebhookCommand($webhookConfig, string $url, string $bodyArg): string
    {

        $escapedBody = escapeshellarg($bodyArg);
        $cmd = ROOT . DS . 'bin' . DS . 'cake webhook ' .
            escapeshellarg($url) . ' ' .
            escapeshellarg(strtolower($webhookConfig->method ?? 'post')) . ' ' .
            $escapedBody;

        return $cmd;
    }


    private function safeJsonEncode($data): string
    {
        $clean = $this->sanitizeJson($data);
        $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            Log::error('safeJsonEncode failed: ' . json_last_error_msg());
            return '{}';
        }

        return $json;
    }

    private function sanitizeJson($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeJson($value);
            }
            return $data;
        }

        if (is_object($data)) {
            if ($data instanceof \DateTimeInterface) {
                return $data->format('Y-m-d H:i:s');
            }
            return (array)$data; // fallback: convert object to array
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return $data;
    }

    /**
     * Recursively remove sensitive or excluded fields from the webhook body.
     */
    private function sanitizeWebhookBody(array $data, array $excluded = self::EXCLUDED_FIELDS): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            // Normalize to lowercase for safety
            $lowerKey = strtolower((string)$key);

            // If key contains any excluded term (e.g. 'password', 'security_group_id'), skip
            $isExcluded = false;
            foreach ($excluded as $term) {
                if (strpos($lowerKey, strtolower($term)) !== false) {
                    $isExcluded = true;
                    break;
                }
            }

            if ($isExcluded) {
                continue;
            }

            // Recurse into nested arrays
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeWebhookBody($value, $excluded);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
    /**
     * Safely replaces ${placeholders} inside JSON templates.
     */
    private function interpolateJsonTemplate(string $template, array $values): array
    {
        $decoded = json_decode($template, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON template');
        }

        $replacePlaceholders = function (&$item) use (&$replacePlaceholders, $values) {
            if (is_array($item)) {
                foreach ($item as &$child) {
                    $replacePlaceholders($child);
                }
            } elseif (is_string($item)) {
                foreach ($values as $key => $val) {
                    $safe = is_scalar($val)
                        ? (string)$val
                        : json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $item = str_replace('${' . $key . '}', $safe, $item);
                }
            }
        };

        $replacePlaceholders($decoded);
        return $decoded;
    }

    public function triggerCommandDelete(string $commandName, ?string $openemisNo, $entity): bool
    {
        // --- Validation guards ---
        if (empty($commandName)) {
            Log::warning("[Webhook] triggerCommandDelete: missing commandName");
            return false;
        }

        if (empty($entity) || !$entity instanceof Entity) {
            Log::warning("[Webhook] triggerCommandDelete: invalid entity");
            return false;
        }

        // --- Build the body ---
        $body = $entity->toArray();
        $body['deleted_at'] = date('Y-m-d H:i:s');
        $body['deleted_by'] = !empty($openemisNo) ? $openemisNo : 'system';

        // --- POCOR-9257: Queue webhook for async processing ---
        try {
            $WebhookQueue = TableRegistry::getTableLocator()->get('Alert.WebhookQueue'); //POCOR-9257: moved to Alert plugin
            $user = $this->resolveCurrentUser();
            $result = $WebhookQueue->queueWebhook($commandName, $body, $user);
            if ($result) {
                // Log::debug("[Webhook] {$commandName} queued for entity ID: " . ($entity->id ?? 'unknown'));
                return true;
            } else {
                Log::warning("[Webhook] Failed to queue {$commandName} for entity ID: " . ($entity->id ?? 'unknown'));
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("[Webhook] {$commandName} queueing failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Safely resolve current user for audit / webhook.
     * // POCOR-9403
     */
    public function resolveCurrentUser(): ?array
    {
        try {
            // Try the Auth component first
            if (!empty($this->Auth) && $this->Auth->user()) {
                return $this->Auth->user();
            }
            $request = new ServerRequest();
            $session = $request->getSession();
            if ($session && $session->check('Auth.User.id')) {
                $userId = $session->read('Auth.User.id');

                $Users = TableRegistry::getTableLocator()->get('User.Users');
                $user = $Users->find('all')
                    ->where([
                        $Users->aliasField('id')
                        => $userId])->first();

                return $user ? $user->toArray() : null;
            }
        } catch (\Throwable $e) {
            Log::warning('User resolution failed: ' . $e->getMessage());

        }

        return null;
    }

    /**
     * Prepares the webhook body for any model, with optional child associations.
     */
    public function prepareWebhookBody($tableAlias, Entity $entity, array $contain = []): array
    {
        $Table = TableRegistry::getTableLocator()->get($tableAlias);

        // Fetch full entity with child models if available
        $record = $Table->find()
            ->where([$Table->aliasField('id') => $entity->id])
            ->contain($contain)
            ->first();

        // Fallback if hard-deleted or not found
        if (!$record) {
            $record = $entity;
        }

        // Convert to array safely
        $body = $record->toArray();

        return $body;
    }
}
