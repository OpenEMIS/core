<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionBusesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('TransportStatuses', ['className' => 'Transport.TransportStatuses']);
        $this->belongsTo('BusTypes', ['className' => 'Transport.BusTypes']);
        $this->belongsTo('InstitutionTransportProviders', ['className' => 'Institution.InstitutionTransportProviders']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->belongsToMany('TransportFeatures', [
			'className' => 'Transport.TransportFeatures',
			'joinTable' => 'institution_buses_transport_features',
			'foreignKey' => 'institution_bus_id',
			'targetForeignKey' => 'transport_feature_id',
			'through' => 'Institution.InstitutionBusesTransportFeatures',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);
    }

	public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

		return $validator
			->add('plate_number', 'ruleUnique', [
				'rule' => 'validateUnique',
				'provider' => 'table'
			]);
    }

	public function findView(Query $query, array $options)
    {
        $query->contain(['TransportFeatures']);

        return $query;
    }

	public function findEdit(Query $query, array $options)
    {
        $query->contain(['TransportFeatures']);

        return $query;
    }
}
