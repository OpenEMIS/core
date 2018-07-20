<?php
namespace Feeder\Model\Table;

use App\Model\Table\ControllerActionTable;

class FeedersInstitutionsTable  extends ControllerActionTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);
        $this->belongsTo('OutgoingInstitutions', ['className' => 'Feeder.OutgoingInstitutions', 'foreignKey' => 'institution_id']]);
		$this->belongsTo('IncomingInstitutions', ['className' => 'Feeder.IncomingInstitutions', 'foreignKey' => 'feeder_institution_id']);
	}
}
