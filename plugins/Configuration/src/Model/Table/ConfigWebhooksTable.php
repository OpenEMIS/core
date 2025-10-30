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
    const string OPEN_EMIS_EXAMS = 'OpenEMIS Exams';
    const array EXCLUDED_FIELDS = ['password', 'security_group_id', 'super_admin'];
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
            if (!empty($eventDef['model'])) {
                try {
                    $table = TableRegistry::getTableLocator()->get($eventDef['model']);
                    $columns = $table->getSchema()->columns();
                    $excluded = $eventDef['excluded'] ?? self::EXCLUDED_FIELDS;

                    foreach ($columns as $column) {
                        if (!in_array($column, $excluded, true)) {
                            $placeholderName = Inflector::humanize(Inflector::underscore($column));
                            $autoPlaceholder = '${' . $column . '}';

                            // ✅ Keep user-defined placeholders, add missing ones
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
        $institution_fields = [
            __('Institution ID') => '${id}',
            __('Institution Name') => '${name}',
            __('Alternative Name') => '${alternative_name}',
            __('Institution Code') => '${code}',
            __('Classification') => '${classification}',
            __('Institution Sector') => '${institution_sector}',
            __('Institution Type') => '${institution_type}',
            __('Gender') => '${gender}',
            __('Date Opened') => '${date_opened}',
            __('Address') => '${address}',
            __('Postal Code') => '${postal_code}',
            __('Locality') => '${locality}',
            __('Latitude') => '${latitude}',
            __('Longitude') => '${longitude}',
            __('Area Education ID') => '${area_education_id}',
            __('Area Education') => '${area_education}',
            __('Area Administrative ID') => '${area_administrative_id}',
            __('Area Administrative') => '${area_administrative}',
            __('Contact Person') => '${contact_person}',
            __('Telephone') => '${telephone}',
            __('Email') => '${email}',
            __('Website') => '${website}',
            __('Custom Fields') => '${custom_fields}'
        ];
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
                'placeholders' => []
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
                'placeholders' => []
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
                'placeholders' => []
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
                'placeholders' => []
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
                'placeholders' => []
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
                'placeholders' => []
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
                'placeholders' => []
            ],
            'education_programme_update' => [
                'code' => 'education_programme_update',
                'label' => 'Education Programme Update',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_system_add' => [
                'code' => 'education_system_add',
                'label' => 'Education System Add',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'education_system_delete' => [
                'code' => 'education_system_delete',
                'label' => 'Education System Delete',
                'model' => 'Education.EducationSystems',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
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
                'placeholders' => []
            ],
            'education_subject_update' => [
                'code' => 'education_subject_update',
                'label' => 'Education Subject Update',
                'model' => 'Education.EducationSubjects',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
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
                'placeholders' => []
            ],
            'institution_class_update' => [
                'code' => 'institution_class_update',
                'label' => 'Institution Class Update',
                'model' => 'Institution.InstitutionClasses',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'security_role_create' => [
                'code' => 'security_role_delete',
                'label' => 'Security Role Create',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'security_role_delete' => [
                'code' => 'security_role_delete',
                'label' => 'Security Role Delete',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'role_update' => [
                'code' => 'security_role_delete',
                'label' => 'Security Role Update',
                'model' => 'Security.SecurityRoles',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => []
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
                'placeholders' => []
            ],
            'security_user_update' => [
                'code' => 'security_user_update',
                'label' => 'Security User Update',
                'model' => 'User.Users',
                'excluded' =>  self::EXCLUDED_FIELDS,
                'placeholders' => []
            ],
            'institution_create' => [
                'code' => 'institution_create',
                'label' => 'Institution Create',
                'model' => 'Institution.Institutions',
                'excluded' => self::EXCLUDED_FIELDS,
                'placeholders' => $institution_fields
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
                'placeholders' => $institution_fields
            ],

//            'institution_subject_create' => [
//                'code' => 'institution_subject_create',
//                'label' => 'Subject Create',
//                'model' => 'Institution.InstitutionSubjects',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_subject_delete' => [
//                'code' => 'sinstitution_ubject_delete',
//                'label' => 'Subject Delete',
//                'model' => 'Institution.InstitutionSubjects',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_subject_update' => [
//                'code' => 'institution_subject_update',
//                'label' => 'Subject Update',
//                'model' => 'Institution.InstitutionSubjects',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ]
//            'institution_staff_create' => [
//                'code' => 'institution_staff_create',
//                'label' => 'Staff Create',
//                'model' => 'Institution.Staff',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_sstaff_delete' => [
//                'code' => 'institution_staff_delete',
//                'label' => 'Staff Delete',
//                'model' => 'Institution.Staff',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_staff_update' => [
//                'code' => 'institution_staff_update',
//                'label' => 'Staff Update',
//                'model' => 'Institution.Staff',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'staff_attendance_update' => [
//                'code' => 'staff_attendance_update',
//                'label' => 'Staff Attendance Update',
//                'model' => 'Institution.StudentAbsencesPeriodDetails',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'student_attendance_update' => [
//                'code' => 'student_attendance_update',
//                'label' => 'Staff Attendance Update',
//                'model' => 'Institution.StudentAbsencesPeriodDetails',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_student_create' => [
//                'code' => 'institution_student_create',
//                'label' => 'Student Create',
//                'model' => 'Institution.Students',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_student_delete' => [
//                'code' => 'institution_student_delete',
//                'label' => 'Student Delete',
//                'model' => 'Institution.Students',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
//            'institution_student_update' => [
//                'code' => 'institution_student_update',
//                'label' => 'Student Update',
//                'model' => 'Institution.Students',
//                'excluded' => self::EXCLUDED_FIELDS,
//                'placeholders' => []
//            ],
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

    public function triggerCommand($eventKey, $body = [])
    {
        $configItems = self::getDynamicTableInstance('Configuration.ConfigItems');

        $webhookConfig = $this->find()
            ->select([
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
            ->first();

        if (empty($webhookConfig)) {
            return; // No active webhook config found
        }

        $url = trim($webhookConfig->url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::write('warning', "Invalid URL for webhook [$eventKey]: $url");
            return;
        }

        $queryTemplate = $webhookConfig->query_template;
        $bodyTemplate = $webhookConfig->body_template;

        // 🧩 Fill placeholders in query template
        $queryParams = '';
        if (!empty($queryTemplate)) {
            // Substitute placeholders in queryTemplate
            foreach ($body as $key => $value) {
                $queryTemplate = str_replace('${' . $key . '}', urlencode((string)$value), $queryTemplate);
            }

            // If template starts with '?', treat it as query params
            if (strpos($queryTemplate, '?') === 0) {
                $queryParams = ltrim($queryTemplate, '?');
                $url .= (strpos($url, '?') === false ? '?' : '&') . $queryParams;
            } else {
                // Otherwise treat it as a REST-style path (may start with / or not)
                $url = rtrim($url, '/') . '/' . ltrim($queryTemplate, '/');
            }
        }
        $isJsonLikeTemplate = (
            str_starts_with(trim($bodyTemplate), '{') &&
            str_ends_with(trim($bodyTemplate), '}')
        );

        // Fill placeholders in body template or use raw body
        if (!empty($bodyTemplate)) {
            try {
                $finalBody = $this->interpolateJsonTemplate($bodyTemplate, $body);
            } catch (\Throwable $e) {
                Log::error("Invalid bodyTemplate: " . $e->getMessage());
                $finalBody = $body; // fallback
            }
        } else {
            $finalBody = $body;
        }
        if (is_array($finalBody)) {
            // normal array → save to temp .json file
            $finalBody = $this->sanitizeWebhookBody($finalBody);

            $temp = TMP . 'webhook_' . uniqid('w', true) . '.json';
            file_put_contents($temp, json_encode($finalBody));
            $bodyArg = $temp; // file path
        } elseif (is_string($finalBody) && str_ends_with($finalBody, '.json') && file_exists($finalBody)) {
            // already a JSON file path
            $bodyArg = $finalBody;
        } else {
            // fallback: small JSON string
            $bodyArg = json_encode($finalBody);
        }

        // Build the shell command safely
        $escapedBody = escapeshellarg($bodyArg); // ✅ no json_encode() here
        $cmd = ROOT . DS . 'bin' . DS . 'cake webhook ' . escapeshellarg($url) . ' ' .
            escapeshellarg($webhookConfig->method ?? 'post') . ' ' . $escapedBody;

        // Check if we need to include server params (for OpenEMIS Exams)
        if ($webhookConfig->external_data_webhook_name === self::OPEN_EMIS_EXAMS) {
            $ExternalAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $attributes = $ExternalAttributes
                ->find('list', [
                    'keyField' => 'attribute_field',
                    'valueField' => 'value'
                ])
                ->where([$ExternalAttributes->aliasField('external_data_source_type') => self::OPEN_EMIS_EXAMS])
                ->toArray();

            if (!empty($attributes['username']) && !empty($attributes['password']) && !empty($attributes['api_key'])) {
                $serverParams = [
                    'username' => $attributes['username'],
                    'password' => $attributes['password'],
                    'api_key' => $attributes['api_key'],
                    'api_url' => $attributes['api_url'] // or use fixed API base if needed
                ];

                $escapedParams = escapeshellarg(json_encode($serverParams));
                $cmd .= ' ' . $escapedParams;
            }
        }

        // 📝 Log and run the command
        $logs = ROOT . DS . 'logs' . DS . 'webhook.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd); // fire-and-forget
            Log::write('debug', "Webhook triggered [PID: $pid] CMD: $shellCmd");
        } catch (Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when triggering: ' . $ex->getMessage());
        }
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

        // --- Trigger the command ---
        try {
            $this->triggerCommand($commandName, $body);
            Log::debug("[Webhook] {$commandName} triggered for entity ID: " . ($entity->id ?? 'unknown'));
            return true;
        } catch (\Throwable $e) {
            Log::error("[Webhook] {$commandName} failed: " . $e->getMessage());
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

            // Fallback to session if Auth is unavailable
            $session = TableRegistry::getTableLocator()
                ->get('Configuration.ConfigItems') // any loaded table with session context
                ->getConnection()
                ->getDriver()
                ->getConnection()
                ->session ?? null;

            if (method_exists($this, 'getRequest') && $this->getRequest()->getSession()) {
                $session = $this->getRequest()->getSession();
            }

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
