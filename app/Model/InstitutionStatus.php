<?php
App::uses('AppModel', 'Model');

class InstitutionStatus extends AppModel {
	var $hasMany = array('Institution');
}
