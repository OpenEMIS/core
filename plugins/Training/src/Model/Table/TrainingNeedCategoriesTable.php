<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingNeedCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_need_categories');
        parent::initialize($config);

        $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_need_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
