<?php
namespace Textbook\Model\Table;

use App\Model\Table\ControllerActionTable;

class TextbookConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => 'textbook_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function getTextbookConditionOptions()
    {
        return  $this
                ->find('list')
                ->find('visible')
                ->find('order')
                ->toArray();
    }
}
