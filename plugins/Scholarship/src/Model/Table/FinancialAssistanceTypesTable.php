<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class FinancialAssistanceTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_financial_assistance_types');
        parent::initialize($config);

        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'foreignKey' => 'scholarship_financial_assistance_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function is($id = 0, $type)
    {
        if ($id) {
            $entity = $this->get($id);
            return $entity->code == $type;
        }

        return false;
    }
}
