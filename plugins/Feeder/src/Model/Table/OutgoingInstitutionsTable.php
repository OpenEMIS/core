<?php
namespace Feeder\Model\Table;

use App\Model\Table\ControllerActionTable;

class OutgoingInstitutionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        $this->belongsToMany('IncomingInstitutions', [
            'className' => 'Feeder.IncomingInstitutions',
            'joinTable' => 'feeders_institutions',
            'foreignKey' => 'institution_id',
            'targetForeignKey' => 'feeder_institution_id',
            'through' => 'Feeder.FeedersInstitutions',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}
