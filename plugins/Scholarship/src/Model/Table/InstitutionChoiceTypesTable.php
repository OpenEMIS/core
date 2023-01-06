<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionChoiceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_institution_choice_types');
        parent::initialize($config);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
