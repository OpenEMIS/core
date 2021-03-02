<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionAssociationStaffTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'institution_association_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('CompositeKey');
    }
}
