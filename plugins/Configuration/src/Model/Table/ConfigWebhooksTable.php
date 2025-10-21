<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
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

class ConfigWebhooksTable extends ControllerActionTable
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
    private $eventKeyOptions = [
        'logout' => 'Logout',
        'institutions_create' => 'Institution Create',
        'class_create'  	  => 'Class Create',
        'class_update'    	  => 'Class Update',
        'subject_create'      => 'Subject Create',
        'student_create'      => 'Student Create',
        'student_update'      => 'Student Update',
        'subject_update'      => 'Subject Update',
        'staff_create'    	  => 'Staff Create',
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
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.ConfigItems');
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
            ;
        return $validator;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['WebhookEvents']);

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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['WebhookEvents']);
    }

//    public function editOnInitialize(Event $event, Entity $entity)
//    {
//        $this->request->getData($this->getAlias())['triggered_event']['_ids'] = [];
//        foreach ($entity->webhook_events as $event) {
//            $this->request->getData($this->getAlias())['triggered_event']['_ids'][] = $event->event_key;
//        }
//    }

    public function beforeAction(Event $event, ArrayObject $extra)
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

    public function onGetTemplatePlaceholdersElement(Event $event, $action, $entity, $attr, $options = [])
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
            if (empty($placeholders) && !empty($eventDef['model'])) {
                try {
                    $table = TableRegistry::getTableLocator()->get($eventDef['model']);
                    $columns = $table->getSchema()->columns();
                    $excluded = $eventDef['excluded'] ?? ['id', 'created', 'modified'];

                    foreach ($columns as $column) {
                        if (!in_array($column, $excluded, true)) {
                            $placeholders[$column] = '${' . $column . '}';
                        }
                    }
                } catch (\Exception $e) {
                    // Optional: log warning here if needed
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


    public function onGetExternalDataSource(Event $event, Entity $entity)
    {
//        dd($event);
        $external_data_source_id = $entity->external_data_source_id;
        if(isset($external_data_source_id) && $external_data_source_id > 0)
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $externalDataOptions = $ConfigItems->find('list')
            ->where(['id' => $external_data_source_id
            ])
            ->toArray();
        return __($externalDataOptions[$external_data_source_id]);
    }
    public function onUpdateFieldExternalDataSourceId(Event $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldUrl(Event $event, array $attr, $action, ServerRequest $request)
    {
        $entity = $attr['entity'];
        if ($action == 'add' || $action == 'edit') {
//            dd($entity);
            $external_data_source_id = $entity->external_data_source_id;
            if ($external_data_source_id) {
                $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
                $externalDataExam = $ConfigItems->find('All')
                    ->where(['id' => $external_data_source_id,
                        'code' => 'external_data_source_webhooks_exams'
                    ])
                    ->first();
                if ($externalDataExam) {
                    $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes');
                    $attributes = $ExternalDataSourceAttributes
                        ->find('list', [
                            'keyField' => 'attribute_field',
                            'valueField' => 'value'
                        ])
                        ->where([
                            $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $externalDataExam->name
                        ])
                        ->orderAsc('attribute_field')
                        ->toArray();
                    $api_url = $attributes['api_url'];
                    if (!empty($attributes) && isset($api_url)) {
                        $attr['value'] = $api_url;
                        $attr['attr']['value'] = $api_url;
                        $attr['default_value'] = $api_url;
                        $attr['attr']['default_value'] = $api_url;
                        $attr['type'] = 'readonly';
//                        return
                    }

                } else {
                    if ($entity->isDirty('url')) {
                        $url = $entity->getOriginal('url');
                        $attr['value'] = $url;
                        $attr['attr']['value'] = $url;
                        $attr['default_value'] = $url;
                        $attr['attr']['default_value'] = $url;
                    }
                }

            };

        }
//        dd($attr);
        return $attr;
    }
    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
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

    public function onUpdateFieldEventKey(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit'])) {
            $attr['type'] = 'select';
            $attr['options'] = self::getEventSelectOptions();
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onGetEventKey(Event $event, Entity $entity)
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'external_data_source_id') {
            return __('External Server');
        } elseif ($field == 'event_key') {
            return __('Triggered Event');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public static function getEvents(): array
    {
        return [
            'logout' => [
                'code' => 'logout',
                'label' => 'Logout',
                'model' => null,
                'excluded' => [],
                'placeholders' => [
                    'openemis_no' => '${openemis_no}',
                    'logout_time' => '${logout_time}'
                ]
            ],
            'academic_period_create' => [
                'code' => 'academic_period_create',
                'label' => 'Academic Period Create',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'academic_period_delete' => [
                'code' => 'academic_period_delete',
                'label' => 'Academic Period Delete',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'academic_period_update' => [
                'code' => 'academic_period_update',
                'label' => 'Academic Period Update',
                'model' => 'AcademicPeriod.AcademicPeriods',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'area_education_create' => [
                'code' => 'area_education_create',
                'label' => 'Area Education Create',
                'model' => 'Area.Areas',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'area_education_delete' => [
                'code' => 'area_education_delete',
                'label' => 'Area Education Delete',
                'model' => 'Area.Areas',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'area_education_update' => [
                'code' => 'area_education_update',
                'label' => 'Area Education Update',
                'model' => 'Area.Areas',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'attendance_update' => [
                'code' => 'attendance_update',
                'label' => 'Student Attendance Update',
                'model' => 'Institution.StudentAbsencesPeriodDetails',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'class_create' => [
                'code' => 'class_create',
                'label' => 'Class Create',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'class_delete' => [
                'code' => 'class_delete',
                'label' => 'Class Delete',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'class_update' => [
                'code' => 'class_update',
                'label' => 'Class Update',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_cycle_create' => [
                'code' => 'education_cycle_create',
                'label' => 'Education Structure Cycle Create',
                'model' => 'Education.EducationCycles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_cycle_delete' => [
                'code' => 'education_cycle_delete',
                'label' => 'Education Structure Cycle Delete',
                'model' => 'Education.EducationCycles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_cycle_update' => [
                'code' => 'education_cycle_update',
                'label' => 'Education Structure Cycle Update',
                'model' => 'Education.EducationCycles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_create' => [
                'code' => 'education_grade_create',
                'label' => 'Education Grade Create',
                'model' => 'Education.EducationGrades',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_delete' => [
                'code' => 'education_grade_delete',
                'label' => 'Education Grade Delete',
                'model' => 'Education.EducationGrades',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_update' => [
                'code' => 'education_grade_update',
                'label' => 'Education Grade Update',
                'model' => 'EducationGrades',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_subject_create' => [
                'code' => 'education_grade_subject_create',
                'label' => 'Education Grade Subject Create',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_subject_delete' => [
                'code' => 'education_grade_subject_delete',
                'label' => 'Education Grade Subject Delete',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_grade_subject_update' => [
                'code' => 'education_grade_subject_update',
                'label' => 'Education Grade Subject Update',
                'model' => 'Education.EducationGradesSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_level_create' => [
                'code' => 'education_level_create',
                'label' => 'Education Structure Level Create',
                'model' => 'Education.EducationLevels',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_level_delete' => [
                'code' => 'education_level_delete',
                'label' => 'Education Structure Level Delete',
                'model' => 'Education.EducationLevels',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_level_update' => [
                'code' => 'education_level_update',
                'label' => 'Education Structure Level Update',
                'model' => 'Education.EducationLevels',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_programme_create' => [
                'code' => 'education_programme_create',
                'label' => 'Education Programme Create',
                'model' => 'Education.EducationProgrammes',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_programme_delete' => [
                'code' => 'education_programme_delete',
                'label' => 'Education Programme Delete',
                'model' => 'Education.EducationProgrammes',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_programme_update' => [
                'code' => 'education_programme_update',
                'label' => 'Education Programme Update',
                'model' => 'Education.EducationSystems',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_structure_system_delete' => [
                'code' => 'education_structure_system_delete',
                'label' => 'Education Structure System Delete',
                'model' => 'Education.EducationSystems',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_structure_system_update' => [
                'code' => 'education_structure_system_update',
                'label' => 'Education Structure System Update',
                'model' => 'Education.EducationSystems',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_subject_create' => [
                'code' => 'education_subject_create',
                'label' => 'Education Subject Create',
                'model' => 'Education.EducationSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_subject_delete' => [
                'code' => 'education_subject_delete',
                'label' => 'Education Subject Delete',
                'model' => 'Education.EducationSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'education_subject_update' => [
                'code' => 'education_subject_update',
                'label' => 'Education Subject Update',
                'model' => 'Education.EducationSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'institutions_create' => [
                'code' => 'institutions_create',
                'label' => 'Institution Create',
                'model' => 'Institution.Institutions',
                'excluded' => ['id', 'created', 'modified', 'security_group_id'],
                'placeholders' => []
            ],
            'institutions_delete' => [
                'code' => 'institutions_delete',
                'label' => 'Institution Delete',
                'model' => 'Institution.Institutions',
                'excluded' => ['id', 'created', 'modified', 'security_group_id'],
                'placeholders' => []
            ],
            'institutions_update' => [
                'code' => 'institutions_update',
                'label' => 'Institution Update',
                'model' => 'Institution.Institutions',
                'excluded' => ['id', 'created', 'modified', 'security_group_id'],
                'placeholders' => []
            ],
            'programme_create' => [
                'code' => 'programme_create',
                'label' => 'Programme Create',
                'model' => 'Education.EducationProgrammes',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'programme_delete' => [
                'code' => 'programme_delete',
                'label' => 'Programme Delete',
                'model' => 'Education.EducationProgrammes',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'programme_update' => [
                'code' => 'programme_update',
                'label' => 'Programme Update',
                'model' => 'Education.EducationProgrammes',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'role_create' => [
                'code' => 'role_create',
                'label' => 'Role Create',
                'model' => 'Security.SecurityRoles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'role_delete' => [
                'code' => 'role_delete',
                'label' => 'Role Delete',
                'model' => 'Security.SecurityRoles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'role_update' => [
                'code' => 'role_update',
                'label' => 'Role Update',
                'model' => 'Security.SecurityRoles',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'security_user_delete' => [
                'code' => 'security_user_delete',
                'label' => 'Delete Security User',
                'model' => 'User.Users',
                'excluded' => ['id', 'password', 'created', 'modified'],
                'placeholders' => []
            ],
            'staff_create' => [
                'code' => 'staff_create',
                'label' => 'Staff Create',
                'model' => 'Institution.Staff',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'staff_delete' => [
                'code' => 'staff_delete',
                'label' => 'Staff Delete',
                'model' => 'Institution.Staff',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'staff_update' => [
                'code' => 'staff_update',
                'label' => 'Staff Update',
                'model' => 'Institution.Staff',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'student_create' => [
                'code' => 'student_create',
                'label' => 'Student Create',
                'model' => 'Institution.Students',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'student_delete' => [
                'code' => 'student_delete',
                'label' => 'Student Delete',
                'model' => 'Institution.Students',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'student_update' => [
                'code' => 'student_update',
                'label' => 'Student Update',
                'model' => 'Institution.Students',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'subject_create' => [
                'code' => 'subject_create',
                'label' => 'Subject Create',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'subject_delete' => [
                'code' => 'subject_delete',
                'label' => 'Subject Delete',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ],
            'subject_update' => [
                'code' => 'subject_update',
                'label' => 'Subject Update',
                'model' => 'Institution.InstitutionSubjects',
                'excluded' => ['id', 'created', 'modified'],
                'placeholders' => []
            ]
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
}
