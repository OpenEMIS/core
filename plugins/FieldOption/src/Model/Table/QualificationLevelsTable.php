<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class QualificationLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('qualification_levels');
        parent::initialize($config);
        $this->hasMany('QualificationTitles', ['className' => 'FieldOption.QualificationTitles']);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices']);

        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }
}
