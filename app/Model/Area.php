<?php
App::uses('AppModel', 'Model');

class Area extends AppModel {
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the code for the Area.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'There are duplicate area code.'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the name for the Area.'
			)
		)
	);
	
	public $belongsTo = array('AreaLevel');
}
