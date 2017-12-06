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
use App\Model\Table\AppTable;

class InstitutionTripsTable extends AppTable
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
}
