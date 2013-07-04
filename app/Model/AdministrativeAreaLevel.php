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

class AdministrativeAreaLevel extends AppModel {
	public $hasMany = array('AdministrativeArea');

	public function saveAreaLevelData($data) {
		$keys = array();
		$levels = array();
		
		if(isset($data['deleted'])) {
			 unset($data['deleted']);
		}

		// foreach($deleted as $id) {
		// 	$this->delete($id);
		// }
		// pr($data);

		for($i=0; $i<sizeof($data); $i++) {
			$row = $data[$i];
            $name = isset($row['name'])? trim($row['name']): '';
			if(!empty($name)) {
				if($row['id'] == 0) {
				 	$this->create();
				 }
				$save = $this->save(array('AdministrativeAreaLevel' => $row));
				
				if($row['id'] == 0) {
					$keys[strval($i+1)] = $save['AdministrativeAreaLevel']['id'];
				}
			} /*else if($row['id'] > 0 && $row['male'] == 0 && $row['female'] == 0) {
				$this->delete($row['id']);
			}*/
		}
		return $keys;
	}
}
