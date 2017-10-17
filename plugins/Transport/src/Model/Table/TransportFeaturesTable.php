<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class TransportFeaturesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('transport_features');
        parent::initialize($config);

		$this->belongsToMany('InstitutionBuses', [
			'className' => 'Institution.InstitutionBuses',
			'joinTable' => 'institution_buses_transport_features',
			'foreignKey' => 'transport_feature_id',
			'targetForeignKey' => 'institution_bus_id',
			'through' => 'Institution.InstitutionBusesTransportFeatures',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
