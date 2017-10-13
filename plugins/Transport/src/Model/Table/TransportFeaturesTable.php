<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class TransportFeaturesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('transport_features');
        parent::initialize($config);

		$this->belongsToMany('Buses', [
			'className' => 'Transport.Buses',
			'joinTable' => 'buses_transport_features',
			'foreignKey' => 'transport_feature_id',
			'targetForeignKey' => 'bus_id',
			'through' => 'Transport.BusesTransportFeatures',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
