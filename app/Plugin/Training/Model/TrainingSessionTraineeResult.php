<?php
class TrainingSessionTraineeResult extends TrainingAppModel {	
	public $belongsTo = array(
		'TrainingSessionTrainee' => array(
			'className' => 'TrainingSessionTrainee',
			'foreignKey' => 'training_session_trainee_id'
		),
		'TrainingResultType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'training_result_type_id'
		),
	);

}