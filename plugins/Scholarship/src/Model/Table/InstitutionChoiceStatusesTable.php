<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class InstitutionChoiceStatusesTable extends AppTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_institution_choice_statuses');
        parent::initialize($config);

        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
