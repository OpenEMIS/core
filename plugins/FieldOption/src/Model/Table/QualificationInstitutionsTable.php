<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class QualificationInstitutionsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
