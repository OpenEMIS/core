<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingResultTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_result_types');
        parent::initialize($config);

        $this->hasMany('TrainingCoursesResultTypes', ['className' => 'Training.TrainingCoursesResultTypes', 'foreignKey' => 'training_result_type_id']);
        $this->hasMany('TrainingSessionTraineeResults', ['className' => 'Training.TrainingSessionTraineeResults', 'foreignKey' => 'training_result_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
