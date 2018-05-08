<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionChoiceStatusesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices']);
    }
}
