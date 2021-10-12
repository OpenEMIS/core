<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use App\Model\Table\AppTable;

class InstitutionTripsTable extends ControllerActionTable
{
    public function initialize(array $config)
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
    }

	public function validationDefault(Validator $validator)
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

                        $data = array_key_exists('data', $context) ? $context['data'] : [];
                        if (array_key_exists('institution_bus_id', $data) && !empty($data['institution_bus_id'])) {
                            $busId = $data['institution_bus_id'];

                            try {
                                $InstitutionBuses = TableRegistry::get('Institution.InstitutionBuses');
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

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $tripDays = [];
        if (array_key_exists('days', $data) && array_key_exists('_ids', $data['days']) && !empty($data['days']['_ids'])) {
            foreach ($data['days']['_ids'] as $day) {
                $tripDays[] = [
                    'day' => $day
                ];
            }
        }

        $data['institution_trip_days'] = $tripDays;

        $tripPassengers = [];
        if (array_key_exists('assigned_students', $data) && array_key_exists('_ids', $data['assigned_students']) && !empty($data['assigned_students']['_ids'])) {
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
        $extra['tripTypes'] = $this->request->query('trip_types'); 
        // trips filter

        $extra['elements']['control'] = [
            'name' => 'Institution.Trips/controls',
            'data' => [
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
        $this->field('repeat', ['visible' => true, 'attr' => ['label' => __('Repeat')]]);
        $this->field('days', ['visible' => true, 'attr' => ['label' => __('Days')]]);
        $this->field('institution_transport_provider_id', ['visible' => false]);
        $this->field('institution_bus_id', ['visible' => false]);
        // POCOR-6169 end

        $this->field('comment',['visible' => false]);    
    }

    // POCOR-6169 start
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $tripTypes = $this->request->query('trip_types');

        $institutionProvider = TableRegistry::get('institution_transport_providers');
        $institutionBuses = TableRegistry::get('institution_buses');

        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }

        $query->select([
            $this->aliasField('id') , 
            $this->aliasField('name'), 
            $this->aliasField('repeat'), 
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
        ->innerJoin([$institutionProvider->alias() => $institutionProvider->table()], [
            [$institutionProvider->aliasField('id ='). $this->aliasField('institution_transport_provider_id')],
        ])
        ->innerJoin([$institutionBuses->alias() => $institutionBuses->table()], [
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
                if($row->repeat == 1){
                    $row['repeat'] = 'Yes';
                }else{
                    $row['repeat'] = 'No';
                }

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
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $tripTypes = $this->request->query('trip_types');
        $academicPeriod = ($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent() ;

        $institutionProvider = TableRegistry::get('institution_transport_providers');
        $institutionBuses = TableRegistry::get('institution_buses');

        $query->select([
            $this->aliasField('id') , 
            $this->aliasField('name'), 
            $this->aliasField('repeat'), 
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
        ->innerJoin([$institutionProvider->alias() => $institutionProvider->table()], [
            [$institutionProvider->aliasField('id ='). $this->aliasField('institution_transport_provider_id')],
        ])
        ->innerJoin([$institutionBuses->alias() => $institutionBuses->table()], [
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
                if($row->repeat == 1){
                    $row['repeat'] = 'Yes';
                }else{
                    $row['repeat'] = 'No';
                }

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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
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
            'key'   => 'repeat',
            'field' => 'repeat',
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

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['default'] = $this->AcademicPeriods->getCurrent();
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->field('name', ['attr' => ['label' => __('Name')]]);

        $this->fields['trip_type_id']['type'] = 'select';
        $this->field('trip_type_id', ['attr' => ['label' => __('Trip Type')]]);

        $this->fields['institution_transport_provider_id']['type'] = 'select';
        $this->field('institution_transport_provider_id', ['attr' => ['label' => __('Provider')]]);

        $InstitutionBuses = $this->InstitutionBuses
        ->find('optionList')
        ->where([
            $this->InstitutionBuses->aliasField('institution_transport_provider_id') => $extra->institution_transport_provider_id
        ])
        ->toArray();
        $this->fields['institution_bus_id']['type'] = 'select';
        $this->fields['institution_bus_id']['options'] = $InstitutionBuses;
        $this->field('institution_bus_id', ['attr' => ['label' => __('Bus')]]);
        
        $repeatOptions = [
            1 => __('Yes'),
            0 => __('No')
        ];
        $this->fields['repeat']['type'] = 'select';
        $this->fields['repeat']['default'] = '1';
        $this->fields['repeat']['options'] = $repeatOptions;
        $this->fields['repeat']['required'] = true;
        $this->field('repeat', ['attr' => ['label' => __('Repeat')]]);

        $dayOptions = $this->getDays();
        $this->fields['days']['type'] = 'select';
        $this->fields['days']['options'] = $dayOptions;
        $this->field('days', ['attr' => ['label' => __('Days')]]);
    }
    // POCOR-6169 end

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionTrips';
        $userType = '';
        $this->controller->changePageHeaderTrips($this, $modelAlias, $userType);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    { 
        switch ($field) {
            case 'institution_transport_provider_id':
                return __('Provider');
            case 'transport_status_id': 
                return __('Status');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
