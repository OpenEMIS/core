<?php
App::uses('AppModel', 'Model');

class BatchReport extends ReportsAppModel {
	public $belongsTo = array('Report');
	
}
