<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class TrainingSessionTraineeResultsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
		$this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);
	}

    public function validationDefault(Validator $validator) 
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('result', 'ruleMaxLength', [
                'rule' => ['maxLength', 10]
            ]);
    }

    public function getTrainingSessionResults($sessionId) {
        $results = $this->find()
            ->where([$this->aliasField('training_session_id') => $sessionId])
            ->toArray();

        $returnArray = [];
        foreach ($results as $result) {
            $returnArray[$sessionId][$result['trainee_id']][$result['training_result_type_id']] = $result['result'];
        }
        return $returnArray;
    }
}
