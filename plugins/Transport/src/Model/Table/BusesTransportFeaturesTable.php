<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;

class BusesTransportFeaturesTable extends AppTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('Buses', ['className' => 'Transport.Buses']);
		$this->belongsTo('TransportFeatures', ['className' => 'Transport.TransportFeatures']);
	}
}
