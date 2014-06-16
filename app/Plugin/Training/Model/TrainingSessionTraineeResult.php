<?php
class TrainingSessionTraineeResult extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingSessionTrainee' => array(
			'className' => 'TrainingSessionTrainee',
			'foreignKey' => 'training_session_trainee_id'
		),
		'TrainingSessionResult' => array(
			'className' => 'TrainingSessionResult',
		  	'foreignKey' => 'training_session_result_id'
		),
		'TrainingResultType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'training_result_type_id'
		),
	);

}