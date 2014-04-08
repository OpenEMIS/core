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

class FieldOptionValue extends AppModel {
	public $actsAs = array('FieldOption');
	
	public function getModel($obj) {
		$model = $this;
		if(!is_null($obj['params'])) {
			$params = (array) json_decode($obj['params']);
			if(isset($params['model'])) {
				$model = ClassRegistry::init($params['model']);
			}
		}
		return $model;
	}
	
	public function getAllValues($obj) {
		$model = $this->getModel($obj);
		$data =  $model->getAllOptions();
		return $data;
	}
	
	public function getValue($obj, $id) {
		$model = $this->getModel($obj);
		$model->recursive = 0;
		$data = $model->findById($id);
		return $data;
	}
	
	public function getFields($obj) {
		$model = $this->getModel($obj);
		$data = $model->getOptionFields();
		return $data;
	}
}
?>
