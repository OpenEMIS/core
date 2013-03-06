<?php
App::uses('AppModel', 'Model');

class InstitutionSiteStatus extends AppModel {
	var $hasMany = array('InstitutionSite');//,'InstitutionsSite'
}
