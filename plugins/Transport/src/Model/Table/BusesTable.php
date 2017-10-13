<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;

class BusesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('TransportStatuses', ['className' => 'Transport.TransportStatuses', 'foreignKey' => 'transport_status_id']);
        $this->belongsTo('BusTypes', ['className' => 'Transport.BusTypes', 'foreignKey' => 'bus_type_id']);
        $this->belongsTo('TransportProviders', ['className' => 'Transport.TransportProviders', 'foreignKey' => 'transport_provider_id']);

        $this->belongsToMany('TransportFeatures', [
			'className' => 'Transport.TransportFeatures',
			'joinTable' => 'buses_transport_features',
			'foreignKey' => 'bus_id',
			'targetForeignKey' => 'transport_feature_id',
			'through' => 'Transport.BusesTransportFeatures',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);
    }
}
