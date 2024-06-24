<?php
namespace Training\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

 //POCOR-8256
class TrainingSessionEvaluatorsTable extends AppTable {
	const INTERNAL = 'INTERNAL';
	const EXTERNAL = 'EXTERNAL';

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'evaluator_id']);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('name')
			->allowEmpty('name', function ($context) {
				if (array_key_exists('types', $context['data'])) {
					$type = $context['data']['types'];
					if ($type == self::INTERNAL) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			});
	}
}
