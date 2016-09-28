<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingModeDeliveriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_mode_deliveries');
        parent::initialize($config);

        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_mode_of_delivery_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
