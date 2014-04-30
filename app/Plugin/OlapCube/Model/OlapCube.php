<?php

class OlapCube extends OlapCubeAppModel {
	//public $useTable = 'student_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'OlapCubeDimension' => array(
			'className' => 'OlapCubeDimension',
			'foreignKey' => 'olap_cube_id',
			'dependent' => true
		)
	);
?>
	