<?php
namespace Feeder\Model\Table;

use App\Model\Table\ControllerActionTable;

class IncomingInstitutionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        $this->belongsToMany('OutgoingInstitution', [
            'className' => 'Feeder.OutgoingInstitution',
            'joinTable' => 'feeders_institutions',
            'foreignKey' => 'feeder_institution_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Feeder.FeedersInstitutions',
            'dependent' => true,
            'cascadeCallbacks' = > true
        ]);
    }
}
