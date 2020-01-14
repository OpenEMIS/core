<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionClassesSecondaryStaffTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('SecondaryStaff', ['className' => 'User.Users']);

        $this->addBehavior('CompositeKey');
    }
}
