<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionChoiceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_institution_choice_types');
        parent::initialize($config);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'foreignKey' => 'scholarship_institution_choice_type_id']);
        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
