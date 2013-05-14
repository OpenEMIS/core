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

class CascadeDeleteBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array('cascade' => array());
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	
	public function beforeDelete(Model $model, $cascade = true) {
		$cascadeList = $this->settings[$model->alias]['cascade'];
		$foreignKey = Inflector::underscore($model->alias . 'Id');
		$continue = true;
		try {
			foreach($cascadeList as $table) {
				$cascadeModel = ClassRegistry::init($table);
				$this->log(sprintf('Deleting %s of %s (id: %s)', $table, $model->alias, $model->id) , 'debug');
				$cascadeModel->deleteAll(array($foreignKey => $model->id), true, true);
			}
		} catch(Exception $ex) {
			$this->log($ex->getMessage(), 'error');
			$continue = false;
		}
		return $continue;
	}
}