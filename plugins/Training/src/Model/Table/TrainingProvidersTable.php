<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingProvidersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_providers');
        parent::initialize($config);

        $this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_provider_id']);
        $this->hasMany('TrainingCoursesProviders', ['className' => 'Training.TrainingCoursesProviders', 'foreignKey' => 'training_provider_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
