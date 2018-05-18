<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class SemestersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_semesters');
        parent::initialize($config);

        $this->hasMany('RecipientAcademicStandings', ['className' => 'Scholarship.RecipientAcademicStandings', 'foreignKey' => 'scholarship_semester_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'foreignKey' => 'scholarship_semester_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
