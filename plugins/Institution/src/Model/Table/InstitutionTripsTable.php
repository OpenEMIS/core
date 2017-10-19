<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
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
        $this->hasMany('InstitutionTripPassengers', ['className' => 'Institution.InstitutionTripPassengers', 'dependent' => true, 'cascadeCallbacks' => true]);
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
    }

    public function findIndex(Query $query, array $options)
    {
        $query->contain(['InstitutionTripDays']);

        return $query;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['InstitutionTripDays', 'InstitutionTripPassengers']);

        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['InstitutionTripDays', 'InstitutionTripPassengers']);

        return $query;
    }
}
