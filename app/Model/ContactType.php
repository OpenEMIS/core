<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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

class ContactType extends AppModel {
	public $belongsTo = array('ContactOption');
	public $hasMany = array('StudentContact', 'StaffContact', 'TeacherContact');
	
	public function getLookupVariables() {
		$parent = ClassRegistry::init('ContactOption');
		$list = $parent->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array('model' => 'ContactType', 'conditions' => array('contact_option_id' => $id));
		}
		return $lookup;
	}

	public function getOptions(){
		$data = $this->find('all', array('recursive' => -1, 'conditions'=>array('visible'=>1), 'order' => array('ContactType.order')));
		$list = array();
		foreach($data as $obj){
			$list[$obj['ContactType']['id']] = $obj['ContactType']['name'];
		}

		return $list;
	}

}
