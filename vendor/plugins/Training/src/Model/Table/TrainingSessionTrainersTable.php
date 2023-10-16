<?php
namespace Training\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class TrainingSessionTrainersTable extends AppTable {
	const INTERNAL = 'INTERNAL';
	const EXTERNAL = 'EXTERNAL';

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'trainer_id']);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('name')
			->allowEmpty('name', function ($context) {
				if (array_key_exists('type', $context['data'])) {
					$type = $context['data']['type'];
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
