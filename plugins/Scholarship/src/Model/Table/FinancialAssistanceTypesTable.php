<?php
namespace Scholarship\Model\Table;

use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class FinancialAssistanceTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
