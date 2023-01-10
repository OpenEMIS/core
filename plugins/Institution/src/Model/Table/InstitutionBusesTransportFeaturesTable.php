<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionBusesTransportFeaturesTable extends AppTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('InstitutionBuses', ['className' => 'Institution.InstitutionBuses']);
		$this->belongsTo('TransportFeatures', ['className' => 'Transport.TransportFeatures']);

		$this->addBehavior('CompositeKey');
	}
}
