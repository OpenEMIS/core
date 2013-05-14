<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class EducationLevelIsced extends AppModel {
	public $useTable = 'education_level_isced';
	public $hasMany = array('EducationLevel');
	
	public function getList() {
		$model = get_class($this);
		$list = $this->find('all', array('recursive' => 0, 'order' => array('order')));
		
		$options = array();
		foreach($list as $obj) {
			if($obj[$model]['isced_level'] >= 0) {
				$options[$obj[$model]['id']] = sprintf('Level %d - %s', $obj[$model]['isced_level'], $obj[$model]['name']);
			} else {
				$options[$obj[$model]['id']] = $obj[$model]['name'];
			}
		}
		return $options;
	}
}
