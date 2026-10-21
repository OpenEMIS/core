<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait; // POCOR-6169 <vikas.rathore@mail.valuecoders.com>

class InstitutionTripsTable extends ControllerActionTable
{
    use OptionsTrait;// POCOR-6169 <vikas.rathore@mail.valuecoders.com>

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('TripTypes', ['className' => 'Transport.TripTypes']);
        $this->belongsTo('InstitutionTransportProviders', ['className' => 'Institution.InstitutionTransportProviders']);
        $this->belongsTo('InstitutionBuses', ['className' => 'Institution.InstitutionBuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('InstitutionTripDays', ['className' => 'Institution.InstitutionTripDays', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);
        $this->hasMany('InstitutionTripPassengers', ['className' => 'Institution.InstitutionTripPassengers', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);

        $this->addBehavior('Excel', [
            'excludes' => ['comment', 'institution_id'],
            'pages' => ['index'],
        ]);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionTrips' =>
                ['institution_id']
            ]
        ]);

        // POCOR-9854: required for the 'information_section'/'passengers_section'
        $this->addBehavior('OpenEmis.Section');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => [
                    'validateUnique', [
                        'scope' => 'institution_id'
                    ]
                ],
                'provider' => 'table'
            ])
            ->add('days', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }

                    return true;
                }
            ])
            ->add('assigned_students', 'ruleMaxLimit', [
                'rule' => function ($value, $context) {
                    $model = $context['providers']['table'];
                    if (isset($value['_ids']) && !empty($value['_ids'])) {
                        $passengerCount = sizeof($value['_ids']);

                        $data = isset($context['data']) ? $context['data'] : [];
                        if (isset($data['institution_bus_id']) && !empty($data['institution_bus_id'])) {
                            $busId = $data['institution_bus_id'];

                            try {
                                $InstitutionBuses = TableRegistry::getTableLocator()->get('Institution.InstitutionBuses');
                                $busEntity = $InstitutionBuses->get($busId);

                                if ($busEntity->has('capacity') && $busEntity->capacity > 0) {
                                    $busCapacity = $busEntity->capacity;

                                    if ($passengerCount > $busCapacity) {
                                        return $model->getMessage('Institution.InstitutionTrips.assigned_students.checkMaxLimit', ['sprintf' => $busCapacity]);
                                    }
                                } else {
                                    return $model->getMessage('Institution.InstitutionTrips.assigned_students.busCapacityNotSet');
                                }
                            } catch (RecordNotFoundException $e) {
                                Log::write('debug', $e->getMessage());
                                return $model->getMessage('Institution.InstitutionTrips.assigned_students.busNotFound');
                            }
                        }
                    }

                    return true;
                }
            ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $tripDays = [];
        if (isset($data['days']) && array_key_exists('_ids', $data['days']) && !empty($data['days']['_ids'])) {
            foreach ($data['days']['_ids'] as $day) {
                $tripDays[] = [
                    'day' => $day
                ];
            }
        }

        $data['institution_trip_days'] = $tripDays;

        $tripPassengers = [];
        if (isset($data['assigned_students']) && array_key_exists('_ids', $data['assigned_students']) && !empty($data['assigned_students']['_ids'])) {
            foreach ($data['assigned_students']['_ids'] as $value) {
                $decodedKeys = $this->paramsDecode($value);

                $studentId = $decodedKeys['student_id'];
                $educationGradeId = $decodedKeys['education_grade_id'];
                $academicPeriodId = $decodedKeys['academic_period_id'];
                $institutionId = $decodedKeys['institution_id'];

                $tripPassengers[] = [
                    'student_id' => $studentId,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId,
                    'institution_id' => $institutionId
                ];
            }
        }

        $data['institution_trip_passengers'] = $tripPassengers;
    }

    public function findIndex(Query $query, array $options)
    {
        $query->contain(['InstitutionTripDays']);

        return $query;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain([
            'InstitutionTripDays',
            'InstitutionTripPassengers' => [
                'Students',
                'sort' => [
                    'Students.first_name' => 'ASC',
                    'Students.last_name' => 'ASC'
                ]
            ]
        ]);

        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['InstitutionTripDays', 'InstitutionTripPassengers']);

        return $query;
    }

    public function getDays()
    {
        $days = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday')
        ];

        return $days;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // POCOR-6169 start
        // academic period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        // academic period filter

        // trips filter
        $tripTypes = $this->TripTypes
        ->find('optionList', ['defaultOption' => false])
        ->toArray();

        $tripTypeOptions = [-1 => __('All Trip Types')] + $tripTypes;
        $extra['tripTypes'] = $this->request->getQuery('trip_types');
        // trips filter
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $extra['elements']['control'] = [
            'name' => 'Institution.Trips/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriod'=> $extra['selectedAcademicPeriodOptions'],
                'tripTypeOptions'=> $tripTypeOptions,
                'selectedtripTypes'=> $extra['tripTypes']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->field('academic_period_id', ['visible' => true, 'attr' => ['label' => __('Academic Period')]]);
        $this->field('name', ['visible' => true, 'attr' => ['label' => __('Name')]]);
        $this->field('trip_type_id', ['visible' => true, 'attr' => ['label' => __('Trip Type')]]);
        $this->field('provider', ['visible' => true, 'attr' => ['label' => __('provider')]]);
        $this->field('bus', ['visible' => true, 'attr' => ['label' => __('Bus')]]);
        $this->field('trip_repeat', ['visible' => true, 'attr' => ['label' => __('Repeat')]]);
        $this->field('days', ['visible' => true, 'attr' => ['label' => __('Days')]]);
        $this->field('institution_transport_provider_id', ['visible' => false]);
        $this->field('institution_bus_id', ['visible' => false]);
        // POCOR-6169 end

        $this->field('comment',['visible' => false]);
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Trips','Transport');
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

    // POCOR-6169 start
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        //$institutionId  = $session->read('Institution.Institutions.id');
        $tripTypes = $this->request->getQuery('trip_types');

        $institutionProvider = TableRegistry::getTableLocator()->get('Institution.InstitutionTransportProviders');
        $institutionBuses = TableRegistry::getTableLocator()->get('Institution.InstitutionBuses');

        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }

        $query->select([
            $this->aliasField('id') ,
            $this->aliasField('name'),
            $this->aliasField('trip_repeat'),
            $this->aliasField('academic_period_id'),
            $this->aliasField('trip_type_id'),
            $this->aliasField('institution_id'),
            'provider' => $institutionProvider->aliasField('name'),
            'bus' => $institutionBuses->aliasField('plate_number'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'),
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->contain(['InstitutionTripDays'])
        ->innerJoin([$institutionProvider->getAlias() => $institutionProvider->getTable()], [
            [$institutionProvider->aliasField('id ='). $this->aliasField('institution_transport_provider_id')],
        ])
        ->innerJoin([$institutionBuses->getAlias() => $institutionBuses->getTable()], [
            [$institutionBuses->aliasField('id ='). $this->aliasField('institution_bus_id')],
        ])
        ->group($this->aliasField('id'))
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        if($tripTypes > 0){
            $query
            ->where([
                $this->aliasField('trip_type_id') => $tripTypes
            ]);
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $repeat = $row->trip_repeat;
                $row['trip_repeat'] = $repeat == 1 ? 'Yes' : 'No';

                $dayOptions = $this->getDays();
                $list = [];
                foreach ($row->institution_trip_days as $obj) {
                    $list[$obj->day] = $dayOptions[$obj->day];
                }

                $value = implode(", ", $list);

                $row['days'] = $value;
                return $row;
            });
        });
    }
    // POCOR-6169 end

    // POCOR-6169 start
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (!is_null($request->getQuery()) && array_key_exists('period', $request->getQuery())) {
                $selectedAcademicPeriod = $request->getQuery('period');
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }
        return $selectedAcademicPeriod;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        //$institutionId  = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();
        $tripTypes = $this->request->getQuery('trip_types');
        $academicPeriod = ($this->request->getQuery('period')) ? $this->request->getQuery('period') : $this->AcademicPeriods->getCurrent() ;

        $institutionProvider = TableRegistry::getTableLocator()->get('Institution.InstitutionTransportProviders');
        $institutionBuses = TableRegistry::getTableLocator()->get('Institution.InstitutionBuses');

        $query->select([
            $this->aliasField('id') ,
            $this->aliasField('name'),
            $this->aliasField('trip_repeat'),
            $this->aliasField('academic_period_id'),
            $this->aliasField('trip_type_id'),
            $this->aliasField('institution_id'),
            'provider' => $institutionProvider->aliasField('name'),
            'bus' => $institutionBuses->aliasField('plate_number'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'),
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->contain(['InstitutionTripDays'])
        ->innerJoin([$institutionProvider->getAlias() => $institutionProvider->getTable()], [
            [$institutionProvider->aliasField('id ='). $this->aliasField('institution_transport_provider_id')],
        ])
        ->innerJoin([$institutionBuses->getAlias() => $institutionBuses->getTable()], [
            [$institutionBuses->aliasField('id ='). $this->aliasField('institution_bus_id')],
        ])
        ->group($this->aliasField('id'))
        ->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriod
        ]);
        if($tripTypes > 0){
            $query
            ->where([
                $this->aliasField('trip_type_id') => $tripTypes
            ]);
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                //POCOR-9854
                $repeat = $row->trip_repeat;
                $row['trip_repeat'] = $repeat == 1 ? 'Yes' : 'No';

                $dayOptions = $this->getDays();
                $list = [];
                foreach ($row->institution_trip_days as $obj) {
                    $list[$obj->day] = $dayOptions[$obj->day];
                }

                $value = implode(", ", $list);

                $row['days'] = $value;
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'academic_period_id',
            'field' => 'academic_period_id',
            'type'  => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key'   => 'name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key'   => 'trip_type_id',
            'field' => 'trip_type_id',
            'type'  => 'string',
            'label' => __('Trip Type')
        ];

        $extraField[] = [
            'key'   => 'provider',
            'field' => 'provider',
            'type'  => 'string',
            'label' => __('Provider')
        ];

        $extraField[] = [
            'key'   => 'bus',
            'field' => 'bus',
            'type'  => 'string',
            'label' => __('Bus')
        ];

        $extraField[] = [
            'key'   => 'trip_repeat',
            'field' => 'trip_repeat',
            'type'  => 'string',
            'label' => __('Repeat')
        ];

        $extraField[] = [
            'key'   => 'days',
            'field' => 'days',
            'type'  => 'string',
            'label' => __('Days')
        ];

        $fields->exchangeArray($extraField);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {


        if (($entity->toArray())) {
            if ($entity->has('institution_transport_provider_id')) {
                $this->fields['institution_transport_provider_id']['default'] = $entity->institution_transport_provider_id;
            }
        }else {
            // 1st instance of add
            $entity->institution_transport_provider_id = '';
        }

        $InstitutionBuses = $this->InstitutionBuses
            ->find('optionList')
            ->where([
                $this->InstitutionBuses->aliasField('institution_transport_provider_id') => $entity->institution_transport_provider_id
            ])
            ->toArray();

        unset($InstitutionBuses[0]);

        $this->fields['institution_bus_id']['type'] = 'select';
        $this->fields['institution_bus_id']['options'] = $InstitutionBuses;

        // $this->fields['days']['multiple'] = true;
        // $this->fields['days']['options'] = $InstitutionBuses;

        // POCOR-9854: Academic Period is read-only - current period on Add, the
        // trip's own period on Edit. Passenger options must follow that same
        if ($this->action == 'edit' && !empty($entity->academic_period_id)) {
            $academicPeriodId = $entity->academic_period_id;
        } else {
            if (!empty($this->request->getQuery('period'))) {
                $academicPeriodId = $this->request->getQuery('period');
            } else {
                $academicPeriodId = $this->AcademicPeriods->getCurrent();
            }
        }
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->fields['academic_period_id']['type'] = 'readonly';
        $this->fields['academic_period_id']['value'] = $academicPeriodId;
        $this->fields['academic_period_id']['attr']['value'] = $academicPeriodOptions[$academicPeriodId] ?? '';

        $this->fields['assigned_students']['options'] = $this->getAssignedStudentOptions($academicPeriodId);

        // POCOR-9854: already-saved passengers may no longer be in the "currently
        if ($this->action == 'edit' && $entity->has('institution_trip_passengers') && !empty($entity->institution_trip_passengers)) {
            $Students = TableRegistry::getTableLocator()->get('Institution.Students');
            $conditions = [];
            foreach ($entity->institution_trip_passengers as $passenger) {
                $conditions[] = [
                    'Students.student_id' => $passenger->student_id,
                    'Students.education_grade_id' => $passenger->education_grade_id,
                    'Students.academic_period_id' => $academicPeriodId,
                    'Students.institution_id' => $passenger->institution_id
                ];
            }

            $matchedStudents = $Students
                ->find()
                ->contain(['Users'])
                ->where(['OR' => $conditions])
                ->toArray();

            $studentsByCompositeKey = [];
            foreach ($matchedStudents as $student) {
                $compositeKey = implode('|', [
                    $student->student_id,
                    $student->education_grade_id,
                    $student->academic_period_id,
                    $student->institution_id
                ]);
                $studentsByCompositeKey[$compositeKey] = $student;
            }

            $existingOptions = [];
            foreach ($entity->institution_trip_passengers as $passenger) {
                $compositeKey = implode('|', [
                    $passenger->student_id,
                    $passenger->education_grade_id,
                    $academicPeriodId,
                    $passenger->institution_id
                ]);

                if (!isset($studentsByCompositeKey[$compositeKey])) {
                    continue;
                }

                $encodedKeys = $this->paramsEncode([
                    'student_id' => $passenger->student_id,
                    'education_grade_id' => $passenger->education_grade_id,
                    'academic_period_id' => $academicPeriodId,
                    'institution_id' => $passenger->institution_id
                ]);
                $existingOptions[$encodedKeys] = $studentsByCompositeKey[$compositeKey]->user->name_with_id;
            }

            $this->fields['assigned_students']['options'] = $existingOptions + ($this->fields['assigned_students']['options'] ?? []);
        }
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // POCOR-9854: Academic Period is read-only (current period on Add, the
        // trip's own period on Edit) - value is resolved in addEditAfterAction()
        // where the entity is available.
        $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['label' => __('Academic Period')]]);
        $this->field('information_section', [
            'type' => 'section',
            'title' => __('Information'),
            'visible' => ['edit' => true, 'add' => true]
        ]);

        $this->field('name', ['attr' => ['label' => __('Name')]]);

        $this->fields['trip_type_id']['type'] = 'select';
        $this->field('trip_type_id', ['attr' => ['label' => __('Trip Type')]]);

        $providerOptions = $this->getProviderOptions(); // POCOR-6169 <vikas.rathore@mail.valuecoders.com>
        $this->fields['institution_transport_provider_id']['type'] = 'select';
        $this->fields['institution_transport_provider_id']['options'] = $providerOptions;
        $this->field('institution_transport_provider_id', ['attr' => ['label' => __('Provider')] , 'onChangeReload' => true]); // POCOR-6169 <vikas.rathore@mail.valuecoders.com>

        $this->field('institution_bus_id', ['attr' => ['label' => __('Bus')]]);

        $repeatOptions = [
            1 => __('Yes'),
            0 => __('No')
        ];
        $this->fields['trip_repeat']['type'] = 'select';
        $this->fields['trip_repeat']['default'] = '1';
        $this->fields['trip_repeat']['options'] = $repeatOptions;
        $this->fields['trip_repeat']['required'] = true;
        $this->field('trip_repeat', ['attr' => ['label' => __('Repeat')]]);

        $dayOptions = $this->getDays();

        // POCOR-6169 <vikas.rathore@mail.valuecoders.com>
        $this->fields['days']['options'] = $dayOptions;

        $this->field('days', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Days')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        // POCOR-6169 <vikas.rathore@mail.valuecoders.com>

        // POCOR-9854
        if ($this->action == 'edit' || $this->action == 'add') {
            $this->field('passengers_section', [
                'type' => 'section',
                'title' => __('Passengers'),
                'visible' => ['edit' => true, 'add' => true]
            ]);

            $this->field('assigned_students', [
                'type' => 'chosenSelect',
                'attr' => ['label' => __('Assigned Students'), 'multiple' => true],
                'visible' => ['edit' => true, 'add' => true]
            ]);
        }

        // POCOR-9854
        $this->setFieldOrder([
            'information_section', 'academic_period_id', 'name', 'trip_type_id',
            'institution_transport_provider_id', 'institution_bus_id', 'trip_repeat',
            'days', 'comment', 'passengers_section', 'assigned_students'
        ]);
    }
    // POCOR-6169 end

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['InstitutionTripDays', 'InstitutionTripPassengers']);
        //START:POCOR-6169
        //return $query
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr =[];
                foreach($row->institution_trip_days as $key=> $dayss){
                    $arr[$key] = ['id'=>$dayss['day']];
                }
                $row['days'] = $arr;

                // POCOR-9854: pre-select existing passengers on the Passengers field
                $passengers = [];
                foreach ($row->institution_trip_passengers as $key => $passenger) {
                    $encodedKeys = $this->paramsEncode([
                        'student_id' => $passenger->student_id,
                        'education_grade_id' => $passenger->education_grade_id,
                        // POCOR-9854: same period as the Edit options in addEditAfterAction()
                        'academic_period_id' => $row->academic_period_id,
                        'institution_id' => $passenger->institution_id
                    ]);
                    $passengers[$key] = ['id' => $encodedKeys];
                }
                $row['assigned_students'] = $passengers;

                return $row;
            });
        });
        //END:POCOR-6169
    }

    // view page POCOR-6169
    public function viewAfterAction(EventInterface $event,Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
        $this->setupPassengersView($entity, $extra);
    }
    // view page POCOR-6169

    // setup fields for view and edit POCOR-6152
    public function setupFields(Entity $entity, ArrayObject $extra)
    {
        // academic field view
        if($entity->academic_period_id){
            $entity['academic_period_id'] = $entity->academic_period->name;
        }
        $this->field('academic_period_id',['before' => 'name','attr' => ['label' => __('Academic Period')],'visible' => ['view' => true]]);

        $this->field('trip_type_id',['after' => 'name','visible' => ['view' => true]]);//trip type field

        $this->field('institution_transport_provider_id',['after' => 'trip_type_id','visible' => ['view' => true]]); //provider field

        //set bus field
        if($entity->institution_bus){
            $entity['institution_buss'] = $entity->institution_bus->plate_number;
        }
        $this->field('institution_buss',['after' => 'institution_transport_provider_id','attr' => ['label' => __('Bus')],'visible' => ['view' => true]]);

        // repeat field view
        if($entity->trip_repeat == 1){
            $entity['trip_repeat'] = 'Yes';
        }else{
            $entity['trip_repeat'] = 'No';
        }
        $this->field('trip_repeat',['after' => 'institution_buss','visible' => ['view' => true]]);

        $this->field('institution_bus_id',['visible' => ['view' => false]]);

    }
    // setup fields for view and edit POCOR-6152

    // POCOR-9854
    private function setupPassengersView(Entity $entity, ArrayObject $extra)
    {
        $extra['selectedGrade'] = -1;
        $extra['selectedStatus'] = -1;
        $extra['selectedGender'] = -1;
        if (array_key_exists('queryString', $this->request->getQuery())) {
            $queryString = $this->paramsDecode($this->request->getQuery('queryString'));
            if (!empty($queryString)) {
                if (isset($queryString['grade'])) {
                    $extra['selectedGrade'] = $queryString['grade'];
                }
                if (isset($queryString['status'])) {
                    $extra['selectedStatus'] = $queryString['status'];
                }
                if (isset($queryString['gender'])) {
                    $extra['selectedGender'] = $queryString['gender'];
                }
            }
        }

        $passengers = [];
        if ($entity->has('institution_trip_passengers') && !empty($entity->institution_trip_passengers)) {
            $Students = TableRegistry::getTableLocator()->get('Institution.Students');

            $conditions = [];
            foreach ($entity->institution_trip_passengers as $passenger) {
                $conditions[] = [
                    'Students.student_id' => $passenger->student_id,
                    'Students.education_grade_id' => $passenger->education_grade_id,
                    'Students.academic_period_id' => $passenger->academic_period_id,
                    'Students.institution_id' => $passenger->institution_id
                ];
            }

            $passengers = $Students
                ->find()
                ->contain(['Users.Genders', 'Users.SpecialNeeds', 'EducationGrades', 'StudentStatuses'])
                ->where(['OR' => $conditions])
                ->toArray();
        }

        $baseUrl = $this->url($this->action, true);
        $params = $this->getQueryString();

        $gradeOptions = [];
        $statusOptions = [];
        $genderOptions = [];

        foreach ($passengers as $key => $student) {
            if (!empty($student->education_grade)) {
                $gradeOptions[$student->education_grade->id]['name'] = $student->education_grade->name;
                $gradeOptions[$student->education_grade->id]['order'] = $student->education_grade->order;
                $params['grade'] = $student->education_grade->id;
                $params['status'] = $extra['selectedStatus'];
                $params['gender'] = $extra['selectedGender'];
                $gradeOptions[$student->education_grade->id]['url'] = $this->setQueryString($baseUrl, $params);
            }

            if (!empty($student->student_status)) {
                $statusOptions[$student->student_status->id]['name'] = $student->student_status->name;
                $statusOptions[$student->student_status->id]['order'] = $student->student_status->id;
                $params['grade'] = $extra['selectedGrade'];
                $params['status'] = $student->student_status->id;
                $params['gender'] = $extra['selectedGender'];
                $statusOptions[$student->student_status->id]['url'] = $this->setQueryString($baseUrl, $params);
            }

            if (!empty($student->user) && !empty($student->user->gender)) {
                $genderOptions[$student->user->gender->id]['name'] = $student->user->gender->name;
                $genderOptions[$student->user->gender->id]['order'] = $student->user->gender->id;
                $params['grade'] = $extra['selectedGrade'];
                $params['status'] = $extra['selectedStatus'];
                $params['gender'] = $student->user->gender->id;
                $genderOptions[$student->user->gender->id]['url'] = $this->setQueryString($baseUrl, $params);
            }

            if ($extra['selectedGrade'] != -1 && (empty($student->education_grade) || $student->education_grade->id != $extra['selectedGrade'])) {
                unset($passengers[$key]);
                continue;
            }
            if ($extra['selectedStatus'] != -1 && (empty($student->student_status) || $student->student_status->id != $extra['selectedStatus'])) {
                unset($passengers[$key]);
                continue;
            }
            if ($extra['selectedGender'] != -1 && (empty($student->user) || empty($student->user->gender) || $student->user->gender->id != $extra['selectedGender'])) {
                unset($passengers[$key]);
                continue;
            }
        }

        $gradeOptions[-1]['name'] = count($gradeOptions) > 0 ? '-- ' . __('All Grades') . ' --' : '-- ' . __('No Options') . ' --';
        $gradeOptions[-1]['order'] = 0;
        $params['grade'] = -1;
        $params['status'] = $extra['selectedStatus'];
        $params['gender'] = $extra['selectedGender'];
        $gradeOptions[-1]['url'] = $this->setQueryString($baseUrl, $params);
        uasort($gradeOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        $statusOptions[-1]['name'] = count($statusOptions) > 0 ? '-- ' . __('All Statuses') . ' --' : '-- ' . __('No Options') . ' --';
        $statusOptions[-1]['order'] = 0;
        $params['grade'] = $extra['selectedGrade'];
        $params['status'] = -1;
        $params['gender'] = $extra['selectedGender'];
        $statusOptions[-1]['url'] = $this->setQueryString($baseUrl, $params);
        uasort($statusOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        $genderOptions[-1]['name'] = count($genderOptions) > 0 ? '-- ' . __('All Genders') . ' --' : '-- ' . __('No Options') . ' --';
        $genderOptions[-1]['order'] = 0;
        $params['grade'] = $extra['selectedGrade'];
        $params['status'] = $extra['selectedStatus'];
        $params['gender'] = -1;
        $genderOptions[-1]['url'] = $this->setQueryString($baseUrl, $params);
        uasort($genderOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        $this->field('passengers', [
            'label' => '',
            'override' => true,
            'after' => 'created',
            'type' => 'element',
            'element' => 'Institution.Trips/passengers',
            'data' => [
                'baseUrl' => $baseUrl,
                'params' => $params,
                'students' => $passengers,
                'filter' => [
                    'education_grades' => ['options' => $gradeOptions, 'selected' => $extra['selectedGrade']],
                    'student_status' => ['options' => $statusOptions, 'selected' => $extra['selectedStatus']],
                    'genders' => ['options' => $genderOptions, 'selected' => $extra['selectedGender']],
                ]
            ],
            'visible' => ['view' => true]
        ]);
    }

    // POCOR-6169 <vikas.rathore@mail.valuecoders.com>
    private function getProviderOptions()
    {
        $session = $this->request->getSession();
        //$institutionId  = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();

        return $this->InstitutionTransportProviders
        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
        ->where([
            $this->InstitutionTransportProviders->aliasField('institution_id') => $institutionId
        ])
        ->toArray();
    }
    // POCOR-6169 <vikas.rathore@mail.valuecoders.com>

    // POCOR-9854: options for the Passengers multi-select, listing students
    // currently enrolled at this institution for the trip's academic period.
    private function getAssignedStudentOptions($academicPeriodId)
    {
        $institutionId = $this->getInstitutionID();

        $Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

        $query = $Students
            ->find()
            ->select([
                $Students->aliasField('student_id'),
                $Students->aliasField('education_grade_id'),
                $Students->aliasField('academic_period_id'),
                $Students->aliasField('institution_id'),
                $Students->Users->aliasField('openemis_no'),
                $Students->Users->aliasField('first_name'),
                $Students->Users->aliasField('middle_name'),
                $Students->Users->aliasField('third_name'),
                $Students->Users->aliasField('last_name'),
                $Students->Users->aliasField('preferred_name')
            ])
            ->contain([$Students->Users->getAlias()])
            ->where([
                $Students->aliasField('institution_id') => $institutionId,
                $Students->aliasField('academic_period_id') => $academicPeriodId,
                $Students->aliasField('student_status_id') => $enrolledStatus
            ])
            ->group([$Students->aliasField('student_id')])
            ->order([
                $Students->Users->aliasField('first_name'),
                $Students->Users->aliasField('last_name')
            ]);

        $options = [];
        foreach ($query as $result) {
            $encodedKeys = $this->paramsEncode([
                'student_id' => $result->student_id,
                'education_grade_id' => $result->education_grade_id,
                'academic_period_id' => $result->academic_period_id,
                'institution_id' => $result->institution_id
            ]);

            $options[$encodedKeys] = $result->user->name_with_id;
        }

        return $options;
    }

    // POCOR-6169 <vikas.rathore@mail.valuecoders.com>
    public function onUpdateFieldInstitutionBusId(EventInterface $event, array $attr, $action, $request) {

        $parentId = $this->request->getData('InstitutionTrips')['institution_transport_provider_id'];
        if(!empty($parentId)){
            $InstitutionBuses = $this->InstitutionBuses
                ->find('optionList')
                ->where([
                    $this->InstitutionBuses->aliasField('institution_transport_provider_id') => $parentId
                ])
                ->toArray();

                $attr['options'] = $InstitutionBuses;
                unset($attr['options'][0]);

                if (empty($InstitutionBuses)) {
                    $attr['empty'] = 'Select';
                }
                return $attr;
        }
    }
    // POCOR-6169 <vikas.rathore@mail.valuecoders.com>

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionTrips';
        $userType = '';
        $this->controller->changePageHeaderTrips($this, $modelAlias, $userType);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'institution_transport_provider_id':
                return __('Provider');
            case 'transport_status_id':
                return __('Status');
            case 'academic_period_id':
                return __('Academic Period');
            case 'trip_type_id':
                return __('Trip Type');
            case 'trip_repeat':
                return __('Repeat');
            case 'provider':
                return __('Provider');
            case 'bus':
                return __('Bus');
            case 'days':
                return __('Days');
            case 'passengers':
                return __('Passengers');
            case 'name':
                return __('Name');
            case 'comment':
                return __('Comment');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified User');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created User');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // POCOR-9854: Academic Period is read-only on Edit - keep the trip's original period
        if (!$entity->isNew() && $entity->isDirty('academic_period_id')) {
            $entity->academic_period_id = $entity->getOriginal('academic_period_id');
        }

        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
